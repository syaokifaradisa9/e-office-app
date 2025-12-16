<?php

namespace Modules\Inventory\Datatables;

use App\Http\Requests\DatatableRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Modules\Inventory\Enums\InventoryPermission;
use Modules\Inventory\Models\Item;

class StockMonitoringDatatableService
{
    private function getStartedQuery(DatatableRequest $request, User $loggedUser): Builder
    {
        $query = Item::query()->with(['category', 'division', 'referenceItem']);

        // Permission Logic
        if ($loggedUser->can(InventoryPermission::MonitorAllStock->value)) {
            // Can see all items including main warehouse
            // No filter needed - show everything
        } elseif ($loggedUser->can(InventoryPermission::MonitorStock->value)) {
            // Can only see their division's items (not main warehouse)
            $query->where('division_id', $loggedUser->division_id);
        } else {
            // No permission, return empty
            $query->whereRaw('1 = 0');
        }

        // Filters
        if ($request->has('name') && $request->name != '') {
            $query->where('name', 'like', '%'.$request->name.'%');
        }

        if ($request->has('category_id') && $request->category_id != 'ALL') {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('stock') && $request->stock != '') {
            $query->where('stock', '>=', $request->stock);
        }

        if ($request->has('stock_max') && $request->stock_max != '') {
            $query->where('stock', '<=', $request->stock_max);
        }

        if ($request->has('unit_of_measure') && $request->unit_of_measure != '') {
            $query->where('unit_of_measure', 'like', '%'.$request->unit_of_measure.'%');
        }

        if ($request->has('division_id') && $request->division_id !== 'ALL') {
            if ($request->division_id === 'MAIN_WAREHOUSE') {
                $query->whereNull('division_id');
            } else {
                $query->where('division_id', $request->division_id);
            }
        }

        // Search
        if ($request->has('search') && $request->search != '') {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('unit_of_measure', 'like', '%'.$request->search.'%');
            });
        }

        // Sorting
        if ($request->has('sort_by') && $request->has('sort_direction')) {
            if ($request->sort_by === 'category.name') {
                $query->join('category_items', 'items.category_id', '=', 'category_items.id')
                    ->orderBy('category_items.name', $request->sort_direction)
                    ->select('items.*');
            } elseif ($request->sort_by === 'division.name') {
                $query->leftJoin('divisions', 'items.division_id', '=', 'divisions.id')
                    ->orderBy('divisions.name', $request->sort_direction)
                    ->select('items.*');
            } else {
                $query->orderBy($request->sort_by, $request->sort_direction);
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query;
    }

    public function getDatatable(DatatableRequest $request, User $loggedUser): mixed
    {
        $query = $this->getStartedQuery($request, $loggedUser);

        return $query->paginate($request->limit ?? 20)->withQueryString();
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
            $options->setColumnWidth(40, 3);
            $options->setColumnWidth(15, 4);
            $options->setColumnWidth(15, 5);

            $writer->openToFile('php://output');

            $headerRow = \OpenSpout\Common\Entity\Row::fromValues([
                'Divisi',
                'Kategori',
                'Nama Barang',
                'Stok',
                'Satuan',
            ]);
            $writer->addRow($headerRow);

            foreach ($data as $item) {
                $row = \OpenSpout\Common\Entity\Row::fromValues([
                    $item->division ? $item->division->name : 'Master',
                    $item->category?->name ?? '-',
                    $item->name,
                    $item->stock,
                    $item->unit_of_measure,
                ]);
                $writer->addRow($row);
            }

            $writer->close();
        }, 'Monitoring Stok Per '.date('d F Y').'.xlsx');
    }
}
