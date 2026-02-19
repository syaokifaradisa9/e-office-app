<?php

namespace Modules\Ticketing\Datatables;

use UnitEnum;
use App\Models\User;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use App\Http\Requests\DatatableRequest;
use Modules\Ticketing\Models\AssetModel;
use Illuminate\Database\Eloquent\Builder;
use Modules\Ticketing\Enums\TicketingPermission;

class AssetModelDatatableService
{
    public function getDatatable(DatatableRequest $request, User $loggedUser): array
    {
        $query = $this->getStartedQuery($request, $loggedUser);

        $limit = $request->input('limit', 10);

        $data = $query->paginate($limit)
            ->through(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'type' => $item->type?->value,
                'division' => $item->division?->name,
                'checklists_count' => $item->checklists_count,
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
                'Nama Asset Model',
                'Tipe',
                'Divisi',
                'Jumlah Checklist',
            ]));

            // Data
            foreach ($data as $index => $item) {
                $writer->addRow(Row::fromValues([
                    $index + 1,
                    $item->name,
                    $item->type?->value ?? '-',
                    $item->division?->name ?? '-',
                    $item->checklists_count ?? 0,
                ]));
            }

            $writer->close();
        }, 'Laporan Data Asset Model Per '.date('d F Y').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function getStartedQuery(DatatableRequest $request, User $loggedUser): Builder
    {
        $query = AssetModel::with('division')->withCount('checklists');

        // Permission Filtering
        $canViewAll = $loggedUser->hasPermissionTo(TicketingPermission::ViewAllAssetModel) || 
                     $loggedUser->hasPermissionTo(TicketingPermission::ManageAssetModel);

        if (!$canViewAll) {
            if ($loggedUser->hasPermissionTo(TicketingPermission::ViewAssetModelDivisi)) {
                $query->where('division_id', $loggedUser->division_id);
            } else {
                $query->whereNull('id');
            }
        }

        // Global Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhereHas('division', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        // Individual Filters
        if ($name = $request->input('name')) {
            $query->where('name', 'like', "%{$name}%");
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($division = $request->input('division')) {
            $query->whereHas('division', fn ($q) => $q->where('name', 'like', "%{$division}%"));
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'name');
        $sortDirection = $request->input('sort_direction', 'asc');

        if (in_array($sortBy, ['name', 'type'])) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('name', 'asc');
        }

        return $query;
    }
}
