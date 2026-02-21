<?php

namespace Modules\Ticketing\Datatables;

use App\Http\Requests\DatatableRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Modules\Ticketing\Enums\TicketingPermission;
use Modules\Ticketing\Models\AssetItem;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class AssetItemDatatableService
{
    public function getDatatable(DatatableRequest $request, User $loggedUser): array
    {
        $query = $this->getStartedQuery($request, $loggedUser);

        $limit = $request->input('limit', 10);

        $data = $query->paginate($limit)
            ->through(fn ($item) => [
                'id' => $item->id,
                'asset_category' => $item->assetCategory?->name,
                'merk' => $item->merk,
                'model' => $item->model,
                'serial_number' => $item->serial_number,
                'division' => $item->division?->name,
                'user' => $item->users->pluck('name')->join(', '),
                'status' => [
                    'value' => $item->status->value,
                    'label' => $item->status->label(),
                    'color' => $item->status->color(),
                ],
                'created_at' => $item->created_at->format('Y-m-d H:i:s'),

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
        $data = $query->orderBy('created_at', 'desc')->get();

        return response()->streamDownload(function () use ($data) {
            $writer = new Writer;
            $writer->openToFile('php://output');

            // Header
            $writer->addRow(Row::fromValues([
                'No',
                'Kategori Asset',
                'Merk',
                'Model',
                'Serial Number',
                'Divisi',
                'User',
                'Status',
                'Tanggal Ditambah',

            ]));

            // Data
            foreach ($data as $index => $item) {
                $writer->addRow(Row::fromValues([
                    $index + 1,
                    $item->assetCategory?->name ?? '-',
                    $item->merk ?? '-',
                    $item->model ?? '-',
                    $item->serial_number ?? '-',
                    $item->division?->name ?? '-',
                    $item->users->pluck('name')->join(', ') ?: '-',
                    $item->status->label(),
                    $item->created_at->format('d/m/Y H:i'),

                ]));
            }

            $writer->close();
        }, 'Laporan Data Asset Per '.date('d F Y').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function getStartedQuery(DatatableRequest $request, User $loggedUser): Builder
    {
        $query = AssetItem::with(['assetCategory', 'division', 'users']);

        // Permission check
        if ($loggedUser->can(TicketingPermission::ViewAllAsset->value)) {
            // No filter
        } elseif ($loggedUser->can(TicketingPermission::ViewDivisionAsset->value)) {
            $query->where('division_id', $loggedUser->division_id);
        } elseif ($loggedUser->can(TicketingPermission::ViewPersonalAsset->value)) {
            $query->whereHas('users', fn ($q) => $q->where('users.id', $loggedUser->id));
        } else {
            $query->whereRaw('1 = 0');
        }

        // Global Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('merk', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%")
                    ->orWhereHas('assetCategory', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('division', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('users', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        // Individual Filters
        if ($merk = $request->input('merk')) {
            $query->where('merk', 'like', "%{$merk}%");
        }
        if ($model = $request->input('model')) {
            $query->where('model', 'like', "%{$model}%");
        }
        if ($serialNumber = $request->input('serial_number')) {
            $query->where('serial_number', 'like', "%{$serialNumber}%");
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');

        if (in_array($sortBy, ['merk', 'model', 'serial_number', 'created_at'])) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query;
    }
}
