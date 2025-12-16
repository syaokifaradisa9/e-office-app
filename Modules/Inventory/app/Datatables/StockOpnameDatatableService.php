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

        // Permission-based filtering
        if ($loggedUser->can(InventoryPermission::ViewAllStockOpname->value)) {
            // Can view all stock opnames
            if ($type === 'warehouse') {
                $query->whereNull('division_id');
            } elseif ($type === 'division') {
                $query->whereNotNull('division_id');
            }
        } elseif ($loggedUser->can(InventoryPermission::ViewWarehouseStockOpname->value)) {
            $query->whereNull('division_id');
        } elseif ($loggedUser->can(InventoryPermission::ViewDivisionStockOpname->value)) {
            $query->where('division_id', $loggedUser->division_id);
        } else {
            $query->whereRaw('1 = 0');
        }

        // Search filter
        if ($request->has('search') && $request->search != '') {
            $query->where(function ($q) use ($request) {
                $q->where('notes', 'like', '%'.$request->search.'%')
                    ->orWhereHas('user', function ($uq) use ($request) {
                        $uq->where('name', 'like', '%'.$request->search.'%');
                    });
            });
        }

        // Status filter
        if ($request->has('status') && $request->status != 'ALL') {
            $query->where('status', $request->status);
        }

        // Division filter
        if ($request->has('division_id') && $request->division_id != 'ALL') {
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

        $result = $query->paginate($request->limit ?? 20)->withQueryString();

        // Transform data to return user and division as strings
        $result->getCollection()->transform(function ($opname) {
            return [
                'id' => $opname->id,
                'opname_date' => \Carbon\Carbon::parse($opname->opname_date)->locale('id')->translatedFormat('d F Y'),
                'user' => $opname->user?->name ?? '-',
                'division' => $opname->division?->name ?? null,
                'status' => $opname->status,
                'created_at' => $opname->created_at?->format('Y-m-d H:i:s'),
            ];
        });

        return $result;
    }

    public function printExcel(DatatableRequest $request, User $loggedUser, string $type = 'all'): mixed
    {
        $query = $this->getStartedQuery($request, $loggedUser, $type);
        $data = $query->get();

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
                'Selisih',
                'Catatan Item',
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
                        $item->physical_stock,
                        $item->difference,
                        $item->notes ?? '-',
                    ]);
                    $writer->addRow($row);
                }
            }

            $writer->close();
        }, 'Laporan Stock Opname Per '.date('d F Y').'.xlsx');
    }
}
