<?php

namespace Modules\Ticketing\Datatables;

use App\Http\Requests\DatatableRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Modules\Ticketing\Enums\TicketingPermission;
use Modules\Ticketing\Models\Maintenance;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class MaintenanceDatatableService
{
    public function getDatatable(DatatableRequest $request, User $loggedUser): array
    {
        $query = $this->getStartedQuery($request, $loggedUser);

        $limit = $request->input('limit', 10);

        $data = $query->paginate($limit)
            ->through(fn ($item) => [
                'id' => $item->id,
                'asset_item' => [
                    'id' => $item->assetItem->id,
                    'category_name' => $item->assetItem->assetCategory?->name,
                    'merk' => $item->assetItem->merk,
                    'model' => $item->assetItem->model,
                    'serial_number' => $item->assetItem->serial_number,
                ],
                'estimation_date' => $item->estimation_date->format('Y-m-d'),
                'actual_date' => $item->actual_date?->format('Y-m-d'),
                'status' => [
                    'value' => $item->status->value,
                    'label' => $item->status->label(),
                ],
                'note' => $item->note,
                'user' => $item->user?->name,
                'is_actionable' => !Maintenance::where('asset_item_id', $item->asset_item_id)
                    ->where('estimation_date', '<', $item->estimation_date)
                    ->where('status', '!=', \Modules\Ticketing\Enums\MaintenanceStatus::CONFIRMED->value)
                    ->exists(),
            ]);

        return [
            'data' => $data->items(),
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
        $data = $query->orderBy('estimation_date', 'asc')->get();

        return response()->streamDownload(function () use ($data) {
            $writer = new Writer;
            $writer->openToFile('php://output');

            // Header
            $writer->addRow(Row::fromValues([
                'No',
                'Asset',
                'Merk / Model',
                'Serial Number',
                'Estimasi Tanggal',
                'Tanggal Aktual',
                'Status',
                'Catatan',
            ]));

            // Data
            foreach ($data as $index => $item) {
                $writer->addRow(Row::fromValues([
                    $index + 1,
                    ($item->assetItem->assetCategory?->name ?? '-'),
                    ($item->assetItem->merk ?? '-') . ' / ' . ($item->assetItem->model ?? '-'),
                    ($item->assetItem->serial_number ?? '-'),
                    $item->estimation_date->format('d/m/Y'),
                    $item->actual_date?->format('d/m/Y') ?? '-',
                    $item->status->value,
                    $item->note ?? '-',
                ]));
            }

            $writer->close();
        }, 'Jadwal Maintenance Per '.date('d F Y').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function getStartedQuery(DatatableRequest $request, User $loggedUser): Builder
    {
        $query = Maintenance::with(['assetItem.assetCategory', 'assetItem.division', 'user']);

        // Permission check
        if ($loggedUser->can(TicketingPermission::ViewAllMaintenance->value)) {
            // No filter
        } elseif ($loggedUser->can(TicketingPermission::ViewDivisionMaintenance->value)) {
            $query->whereHas('assetItem', fn ($q) => $q->where('division_id', $loggedUser->division_id));
        } elseif ($loggedUser->can(TicketingPermission::ViewPersonalAsset->value)) {
            $query->whereHas('assetItem.users', fn ($q) => $q->where('users.id', $loggedUser->id));
        } else {
            $query->whereRaw('1 = 0');
        }

        // Filter by year if provided
        if ($year = $request->input('year')) {
            $query->whereYear('estimation_date', $year);
        }

        // Global Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('assetItem', function($q2) use ($search) {
                    $q2->where('merk', 'like', "%{$search}%")
                       ->orWhere('model', 'like', "%{$search}%")
                       ->orWhere('serial_number', 'like', "%{$search}%")
                        ->orWhereHas('assetCategory', fn ($q3) => $q3->where('name', 'like', "%{$search}%"));
                })->orWhere('note', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'estimation_date');
        $sortDirection = $request->input('sort_direction', 'asc');

        if (in_array($sortBy, ['estimation_date', 'actual_date', 'status'])) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('estimation_date', 'asc');
        }

        return $query;
    }
}
