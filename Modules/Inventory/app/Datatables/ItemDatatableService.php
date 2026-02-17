<?php

namespace Modules\Inventory\Datatables;

use App\Http\Requests\DatatableRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Modules\Inventory\Models\Item;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class ItemDatatableService
{
    public function getDatatable(DatatableRequest $request, User $loggedUser): array
    {
        $query = $this->getStartedQuery($request, $loggedUser);

        $limit = $request->input('limit', 10);

        $data = $query->paginate($limit)
            ->through(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'category' => $item->category?->name,
                'unit_of_measure' => $item->unit_of_measure,
                'stock' => $item->stock,
                'multiplier' => $item->multiplier,
                'reference_item' => $item->referenceItem?->name,
                'description' => $item->description,
                'created_at' => $item->created_at->format('Y-m-d H:i:s'),
            ]);

        return [
            'data' => $data->items(),
            'links' => $data->linkCollection()->toArray(),
            'current_page' => $data->currentPage(),
            'last_page' => $data->lastPage(),
            'per_page' => $data->perPage(),
            'from' => $data->firstItem(),
            'to' => $data->lastItem(),
            'total' => $data->total(),
        ];
    }

    public function printExcel(DatatableRequest $request, User $loggedUser): mixed
    {
        $query = $this->getStartedQuery($request, $loggedUser);
        $data = $query->orderBy('name')->get();

        return response()->streamDownload(function () use ($data) {
            $writer = new Writer;
            $writer->openToFile('php://output');

            // Header
            $writer->addRow(Row::fromValues([
                'No',
                'Nama Barang',
                'Kategori',
                'Satuan',
                'Stok',
                'Multiplier',
                'Referensi',
            ]));

            // Data
            foreach ($data as $index => $item) {
                $writer->addRow(Row::fromValues([
                    $index + 1,
                    $item->name,
                    $item->category?->name ?? '-',
                    $item->unit_of_measure,
                    $item->stock,
                    $item->multiplier ?? 1,
                    $item->referenceItem?->name ?? '-',
                ]));
            }

            $writer->close();
        }, 'Laporan Data Barang Gudang Utama Per '.date('d F Y').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function getStartedQuery(DatatableRequest $request, User $loggedUser): Builder
    {
        $query = Item::with(['category', 'referenceItem']);

        // Menu Barang hanya menampilkan barang gudang utama (division_id = null)
        $query->whereNull('division_id');

        // Global Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('unit_of_measure', 'like', "%{$search}%")
                    ->orWhere('stock', 'like', "%{$search}%")
                    ->orWhereHas('category', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        // Individual Filters
        if ($name = $request->input('name')) {
            $query->where('name', 'like', "%{$name}%");
        }

        if ($category = $request->input('category')) {
            $query->whereHas('category', fn ($q) => $q->where('name', 'like', "%{$category}%"));
        }

        if ($unitOfMeasure = $request->input('unit_of_measure')) {
            $query->where('unit_of_measure', 'like', "%{$unitOfMeasure}%");
        }

        if ($stock = $request->input('stock')) {
            $query->where('stock', $stock);
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'name');
        $sortDirection = $request->input('sort_direction', 'asc');

        if (in_array($sortBy, ['name', 'unit_of_measure', 'stock', 'created_at'])) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('name', 'asc');
        }

        return $query;
    }
}
