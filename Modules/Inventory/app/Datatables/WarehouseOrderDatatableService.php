<?php

namespace Modules\Inventory\Datatables;

use App\Http\Requests\DatatableRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Modules\Inventory\Enums\InventoryPermission;
use Modules\Inventory\Models\WarehouseOrder;

class WarehouseOrderDatatableService
{
    private function getStartedQuery(DatatableRequest $request, User $loggedUser): Builder
    {
        $query = WarehouseOrder::query()->with(['user', 'division', 'carts.item']);

        if ($loggedUser->can(InventoryPermission::ViewAllWarehouseOrder->value)) {
            // User can view all warehouse orders
        } elseif ($loggedUser->can(InventoryPermission::ViewWarehouseOrderDivisi->value)) {
            $query->where('division_id', $loggedUser->division_id);
        } else {
            $query->whereRaw('1 = 0');
        }

        if ($request->has('search') && $request->search != '') {
            $query->where(function ($q) use ($request) {
                $q->where('order_number', 'like', '%'.$request->search.'%')
                    ->orWhere('description', 'like', '%'.$request->search.'%')
                    ->orWhere('notes', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->has('order_number') && $request->order_number != '') {
            $query->where('order_number', 'like', '%'.$request->order_number.'%');
        }

        if ($request->has('status') && $request->status != 'ALL') {
            $query->where('status', $request->status);
        }

        if ($request->has('user_id') && $request->user_id != '') {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->user_id.'%');
            });
        }

        if ($request->has('division_id') && $request->division_id != 'ALL') {
            $query->where('division_id', $request->division_id);
        }

        if ($request->has('created_at') && $request->created_at != '') {
            $date = \Carbon\Carbon::parse($request->created_at);
            $query->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year);
        }

        if ($request->has('sort_by') && $request->has('sort_direction')) {
            $query->orderBy($request->sort_by, $request->sort_direction);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query;
    }

    public function getDatatable(DatatableRequest $request, User $loggedUser): mixed
    {
        $query = $this->getStartedQuery($request, $loggedUser);

        $result = $query->paginate($request->limit ?? 20)->withQueryString();

        // Transform data to return user and division as objects
        $result->getCollection()->transform(function ($order) {
            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'user_id' => $order->user_id,
                'division_id' => $order->division_id,
                'user' => $order->user ? [
                    'id' => $order->user->id,
                    'name' => $order->user->name,
                ] : null,
                'division' => $order->division ? [
                    'id' => $order->division->id,
                    'name' => $order->division->name,
                ] : null,
                'status' => $order->status->value ?? $order->status,
                'created_at' => $order->created_at?->format('Y-m-d H:i:s'),
            ];
        });

        return $result;
    }

    public function printExcel(DatatableRequest $request, User $loggedUser): mixed
    {
        $query = $this->getStartedQuery($request, $loggedUser);
        $data = $query->get();

        return response()->streamDownload(function () use ($data) {
            $writer = new \OpenSpout\Writer\XLSX\Writer;

            $options = $writer->getOptions();
            $options->setColumnWidth(20, 1);
            $options->setColumnWidth(25, 2);
            $options->setColumnWidth(30, 3);
            $options->setColumnWidth(20, 4);
            $options->setColumnWidth(20, 5);
            $options->setColumnWidth(25, 6);
            $options->setColumnWidth(30, 7);

            $writer->openToFile('php://output');

            // ========== SHEET 1: ORDER DATA ==========
            $orderSheet = $writer->getCurrentSheet();
            $orderSheet->setName('Order');

            $headerRow = \OpenSpout\Common\Entity\Row::fromValues([
                'No. Order',
                'Divisi',
                'Deskripsi',
                'Status',
                'Tanggal Dibuat',
                'Dibuat Oleh',
                'Catatan',
            ]);
            $writer->addRow($headerRow);

            foreach ($data as $order) {
                $row = \OpenSpout\Common\Entity\Row::fromValues([
                    $order->order_number,
                    $order->division?->name ?? '-',
                    $order->description ?? '-',
                    $order->status->value ?? '-',
                    $order->created_at->format('d/m/Y H:i'),
                    $order->user?->name ?? '-',
                    $order->notes ?? '-',
                ]);
                $writer->addRow($row);
            }

            // ========== SHEET 2: ORDER DETAIL (ITEMS) ==========
            $detailSheet = $writer->addNewSheetAndMakeItCurrent();
            $detailSheet->setName('Detail');

            $detailHeaderRow = \OpenSpout\Common\Entity\Row::fromValues([
                'No. Order',
                'Divisi',
                'Tanggal',
                'Dibuat Oleh',
                'Nama Barang',
                'Jumlah',
                'Satuan',
            ]);
            $writer->addRow($detailHeaderRow);

            foreach ($data as $order) {
                foreach ($order->carts as $cart) {
                    $row = \OpenSpout\Common\Entity\Row::fromValues([
                        $order->order_number,
                        $order->division?->name ?? '-',
                        $order->created_at->format('d/m/Y'),
                        $order->user?->name ?? '-',
                        $cart->item?->name ?? '-',
                        $cart->quantity,
                        $cart->item?->unit_of_measure ?? '-',
                    ]);
                    $writer->addRow($row);
                }
            }

            $writer->close();
        }, 'Laporan Permintaan Barang Per '.date('d F Y').'.xlsx');
    }
}
