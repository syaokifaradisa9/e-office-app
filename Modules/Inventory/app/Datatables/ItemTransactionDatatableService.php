<?php

namespace Modules\Inventory\Datatables;

use App\Http\Requests\DatatableRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Modules\Inventory\Enums\InventoryPermission;
use Modules\Inventory\Models\ItemTransaction;

class ItemTransactionDatatableService
{
    private function getStartedQuery(DatatableRequest $request, User $loggedUser): Builder
    {
        $query = ItemTransaction::query()->with(['item.division', 'item.category', 'user']);

        // Permission Logic
        if ($loggedUser->can(InventoryPermission::MonitorAllItemTransaction->value)) {
            // Can see all, no filter needed
        } elseif ($loggedUser->can(InventoryPermission::MonitorItemTransaction->value)) {
            // Can only see their division's transactions
            $query->whereHas('item', function ($q) use ($loggedUser) {
                $q->where('division_id', $loggedUser->division_id);
            });
        } else {
            // No permission, return empty
            $query->whereRaw('1 = 0');
        }

        // Date filter
        if ($request->has('date') && $request->date != '') {
            $date = \Carbon\Carbon::parse($request->date);
            $query->whereMonth('date', $date->month)
                ->whereYear('date', $date->year);
        }

        // Item name filter
        if ($request->has('item_name') && $request->item_name != '') {
            $query->whereHas('item', function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->item_name.'%');
            });
        }

        // Quantity filter
        if ($request->has('quantity') && $request->quantity != '') {
            $query->where('quantity', $request->quantity);
        }

        // Division filter
        if ($request->has('division_id') && $request->division_id != 'ALL') {
            $query->whereHas('item', function ($q) use ($request) {
                if ($request->division_id === 'MAIN_WAREHOUSE') {
                    $q->whereNull('division_id');
                } else {
                    $q->where('division_id', $request->division_id);
                }
            });
        }

        // Type filter
        if ($request->has('type') && $request->type != 'ALL' && $request->type != '') {
            $query->where('type', $request->type);
        }

        // User name filter
        if ($request->has('user_name') && $request->user_name != '') {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->user_name.'%');
            });
        }

        // Description filter
        if ($request->has('description') && $request->description != '') {
            $query->where('description', 'like', '%'.$request->description.'%');
        }

        // Search
        if ($request->has('search') && $request->search != '') {
            $query->where(function ($q) use ($request) {
                $q->where('description', 'like', '%'.$request->search.'%')
                    ->orWhereHas('item', function ($iq) use ($request) {
                        $iq->where('name', 'like', '%'.$request->search.'%');
                    });
            });
        }

        // Sorting
        if ($request->has('sort_by') && $request->has('sort_direction')) {
            $query->orderBy($request->sort_by, $request->sort_direction);
        } else {
            $query->orderBy('date', 'desc');
        }

        return $query;
    }

    public function getDatatable(DatatableRequest $request, User $loggedUser): mixed
    {
        $query = $this->getStartedQuery($request, $loggedUser);

        return $query->paginate($request->limit ?? 20)
            ->through(fn ($transaction) => [
                'id' => $transaction->id,
                'date' => \Carbon\Carbon::parse($transaction->date)->locale('id')->translatedFormat('d F Y'),
                'type' => $transaction->type?->label() ?? '-',
                'item' => $transaction->item?->name ?? '-',
                'quantity' => $transaction->quantity,
                'user' => $transaction->user?->name ?? null,
                'description' => $transaction->description,
                'division' => $transaction->item?->division?->name ?? null,
            ])
            ->withQueryString();
    }

    public function printExcel(DatatableRequest $request, User $loggedUser): mixed
    {
        $query = $this->getStartedQuery($request, $loggedUser);
        $data = $query->get();
        $hasMonitorAll = $loggedUser->can(InventoryPermission::MonitorAllItemTransaction->value);

        return response()->streamDownload(function () use ($data, $hasMonitorAll) {
            $writer = new \OpenSpout\Writer\XLSX\Writer;

            $options = $writer->getOptions();
            $options->setColumnWidth(20, 1);
            $options->setColumnWidth(20, 2);
            $options->setColumnWidth(30, 3);
            $options->setColumnWidth(15, 4);
            $options->setColumnWidth(20, 5);

            if ($hasMonitorAll) {
                $options->setColumnWidth(25, 6);
                $options->setColumnWidth(35, 7);
            } else {
                $options->setColumnWidth(35, 6);
            }

            $writer->openToFile('php://output');

            // Header row
            $headers = [
                'Tanggal',
                'Tipe',
                'Nama Barang',
                'Jumlah',
                'Satuan',
            ];

            if ($hasMonitorAll) {
                $headers[] = 'Divisi';
            }

            $headers[] = 'Deskripsi';

            $headerRow = \OpenSpout\Common\Entity\Row::fromValues($headers);
            $writer->addRow($headerRow);

            // Data rows
            foreach ($data as $transaction) {
                $rowData = [
                    \Carbon\Carbon::parse($transaction->date)->format('d/m/Y'),
                    $transaction->type?->label() ?? '-',
                    $transaction->item?->name ?? '-',
                    $transaction->quantity,
                    $transaction->item?->unit_of_measure ?? '-',
                ];

                if ($hasMonitorAll) {
                    $rowData[] = $transaction->item?->division?->name ?? 'Gudang Utama';
                }

                $rowData[] = $transaction->description ?? '-';

                $row = \OpenSpout\Common\Entity\Row::fromValues($rowData);
                $writer->addRow($row);
            }

            $writer->close();
        }, 'Laporan Transaksi Barang Per '.date('d F Y').'.xlsx');
    }
}
