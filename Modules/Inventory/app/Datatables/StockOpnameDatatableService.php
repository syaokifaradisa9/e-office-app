<?php

namespace Modules\Inventory\Datatables;

use App\Http\Requests\DatatableRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Modules\Inventory\Enums\InventoryPermission;
use Modules\Inventory\Models\StockOpname;

class StockOpnameDatatableService
{
    private function getStartedQuery(DatatableRequest $request, User $loggedUser, string $type = 'all'): Builder
    {
        $query = StockOpname::query()->with(['user', 'division', 'items.item']);

        // Type-based filtering
        // Requirement 1: /all shows ALL data without any division_id filter
        // Requirement 14: /warehouse shows division_id = null only
        // Requirement 15: /division shows division_id = user's division_id only
        if ($type === 'warehouse') {
            $query->whereNull('division_id');
        } elseif ($type === 'division') {
            $query->where('division_id', $loggedUser->division_id);
        }
        // type === 'all' → no division filter, show everything

        // Global search filter (Requirement 12)
        if ($request->has('search') && $request->search != '') {
            $query->where(function ($q) use ($request) {
                $q->where('notes', 'like', '%'.$request->search.'%')
                    ->orWhere('status', 'like', '%'.$request->search.'%')
                    ->orWhereHas('user', function ($uq) use ($request) {
                        $uq->where('name', 'like', '%'.$request->search.'%');
                    })
                    ->orWhereHas('division', function ($dq) use ($request) {
                        $dq->where('name', 'like', '%'.$request->search.'%');
                    });
            });
        }

        // Individual column search filters (Requirement 13)
        // Status filter
        if ($request->has('status') && $request->status != '' && $request->status != 'ALL') {
            $query->where('status', $request->status);
        }

        // Division filter
        if ($request->has('division_id') && $request->division_id != '' && $request->division_id != 'ALL') {
            if ($request->division_id === 'MAIN_WAREHOUSE') {
                $query->whereNull('division_id');
            } else {
                $query->where('division_id', $request->division_id);
            }
        }

        // Date filter
        if ($request->has('opname_date') && $request->opname_date != '') {
            $date = \Carbon\Carbon::parse($request->opname_date);
            $query->whereMonth('opname_date', $date->month)
                ->whereYear('opname_date', $date->year);
        }

        // User/petugas filter
        if ($request->has('user') && $request->user != '') {
            $query->whereHas('user', function ($uq) use ($request) {
                $uq->where('name', 'like', '%'.$request->user.'%');
            });
        }

        // Sorting
        if ($request->has('sort_by') && $request->has('sort_direction')) {
            $query->orderBy($request->sort_by, $request->sort_direction);
        } else {
            $query->orderBy('opname_date', 'desc');
        }

        return $query;
    }

    public function getDatatable(DatatableRequest $request, User $loggedUser, string $type = 'all'): mixed
    {
        $query = $this->getStartedQuery($request, $loggedUser, $type);

        // Requirement 12: pagination and limit
        $result = $query->paginate($request->limit ?? 20)->withQueryString();

        // Transform data
        $result->getCollection()->transform(function ($opname) {
            return [
                'id' => $opname->id,
                'opname_date' => \Carbon\Carbon::parse($opname->opname_date)->locale('id')->translatedFormat('d F Y'),
                'user' => $opname->user?->name ?? '-',
                'division' => $opname->division?->name ?? null,
                'status' => $opname->status,
                'notes' => $opname->notes,
                'created_at' => $opname->created_at?->format('Y-m-d H:i:s'),
            ];
        });

        return $result;
    }

    public function printExcel(DatatableRequest $request, User $loggedUser, string $type = 'all'): mixed
    {
        $query = $this->getStartedQuery($request, $loggedUser, $type);
        $data = $query->get();

        $titleSuffix = match ($type) {
            'warehouse' => 'Gudang',
            'division' => 'Divisi',
            default => 'Semua',
        };

        return response()->streamDownload(function () use ($data) {
            $writer = new \OpenSpout\Writer\XLSX\Writer;

            $options = $writer->getOptions();
            $options->setColumnWidth(20, 1);
            $options->setColumnWidth(25, 2);
            $options->setColumnWidth(25, 3);
            $options->setColumnWidth(20, 4);
            $options->setColumnWidth(35, 5);

            $writer->openToFile('php://output');

            // ========== SHEET 1: STOCK OPNAME DATA ==========
            $opnameSheet = $writer->getCurrentSheet();
            $opnameSheet->setName('Stock Opname');

            $headerRow = \OpenSpout\Common\Entity\Row::fromValues([
                'Tanggal',
                'Lokasi',
                'Dibuat Oleh',
                'Status',
                'Catatan',
            ]);
            $writer->addRow($headerRow);

            foreach ($data as $opname) {
                $row = \OpenSpout\Common\Entity\Row::fromValues([
                    \Carbon\Carbon::parse($opname->opname_date)->format('d/m/Y'),
                    $opname->division?->name ?? 'Gudang Utama',
                    $opname->user?->name ?? '-',
                    $opname->status,
                    $opname->notes ?? '-',
                ]);
                $writer->addRow($row);
            }

            // ========== SHEET 2: STOCK OPNAME DETAIL (ITEMS) ==========
            $detailSheet = $writer->addNewSheetAndMakeItCurrent();
            $detailSheet->setName('Detail');

            $options->setColumnWidth(20, 1);
            $options->setColumnWidth(25, 2);
            $options->setColumnWidth(25, 3);
            $options->setColumnWidth(30, 4);
            $options->setColumnWidth(15, 5);
            $options->setColumnWidth(15, 6);
            $options->setColumnWidth(15, 7);
            $options->setColumnWidth(30, 8);

            $detailHeaderRow = \OpenSpout\Common\Entity\Row::fromValues([
                'Tanggal',
                'Lokasi',
                'Dibuat Oleh',
                'Nama Barang',
                'Stok Sistem',
                'Stok Fisik',
                'Stok Final',
                'Selisih',
                'Catatan Item',
                'Catatan Final',
            ]);
            $writer->addRow($detailHeaderRow);

            foreach ($data as $opname) {
                foreach ($opname->items as $item) {
                    $row = \OpenSpout\Common\Entity\Row::fromValues([
                        \Carbon\Carbon::parse($opname->opname_date)->format('d/m/Y'),
                        $opname->division?->name ?? 'Gudang Utama',
                        $opname->user?->name ?? '-',
                        $item->item?->name ?? '-',
                        $item->system_stock,
                        $item->physical_stock ?? '-',
                        $opname->status === 'Selesai' ? $item->final_stock : '-',
                        $item->difference ?? '-',
                        $item->notes ?? '-',
                        $item->final_notes ?? '-',
                    ]);
                    $writer->addRow($row);
                }
            }

            $writer->close();
        }, 'Laporan Stock Opname '.$titleSuffix.' Per '.date('d F Y').'.xlsx');
    }
}
