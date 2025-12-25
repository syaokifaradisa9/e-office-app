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

        $data = $query->orderBy('name')
            ->paginate($limit)
            ->through(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'category' => $item->category?->name,
                'unit_of_measure' => $item->unit_of_measure,
                'stock' => $item->stock,
                'multiplier' => $item->multiplier,
                'reference_item' => $item->referenceItem?->name,
                'created_at' => $item->created_at->format('Y-m-d H:i:s'),
            ]);

        return [
            'data' => $data->items(),
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
            ],
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
        // Untuk melihat stok di semua divisi, gunakan menu Monitoring Stok
        $query->whereNull('division_id');

        // Search filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('category', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        // Category filter
        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        return $query;
    }
}
