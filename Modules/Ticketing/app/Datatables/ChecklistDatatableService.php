<?php

namespace Modules\Ticketing\Datatables;

use App\Models\User;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use App\Http\Requests\DatatableRequest;
use Modules\Ticketing\Models\Checklist;
use Illuminate\Database\Eloquent\Builder;

class ChecklistDatatableService
{
    public function getDatatable(DatatableRequest $request, int $assetCategoryId): array
    {
        $query = $this->getStartedQuery($request, $assetCategoryId);

        $limit = $request->input('limit', 10);

        $data = $query->paginate($limit)
            ->through(fn ($item) => [
                'id' => $item->id,
                'label' => $item->label,
                'description' => $item->description,
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

    public function printExcel(DatatableRequest $request, int $assetCategoryId, string $assetCategoryName): mixed
    {
        $query = $this->getStartedQuery($request, $assetCategoryId);
        $data = $query->orderBy('label')->get();

        return response()->streamDownload(function () use ($data) {
            $writer = new Writer;
            $writer->openToFile('php://output');

            // Header
            $writer->addRow(Row::fromValues([
                'No',
                'Keterangan',
                'Deskripsi',
            ]));

            // Data
            foreach ($data as $index => $item) {
                $writer->addRow(Row::fromValues([
                    $index + 1,
                    $item->label,
                    $item->description ?? '-',
                ]));
            }

            $writer->close();
        }, 'Checklist '.($assetCategoryName).' Per '.date('d F Y').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function getStartedQuery(DatatableRequest $request, int $assetCategoryId): Builder
    {
        $query = Checklist::where('asset_category_id', $assetCategoryId);

        // Global Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('label', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Individual Filters
        if ($label = $request->input('label')) {
            $query->where('label', 'like', "%{$label}%");
        }

        if ($description = $request->input('description')) {
            $query->where('description', 'like', "%{$description}%");
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'label');
        $sortDirection = $request->input('sort_direction', 'asc');

        if (in_array($sortBy, ['label', 'description'])) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('label', 'asc');
        }

        return $query;
    }
}
