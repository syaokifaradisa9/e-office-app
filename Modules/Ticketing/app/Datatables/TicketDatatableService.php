<?php

namespace Modules\Ticketing\Datatables;

use App\Http\Requests\DatatableRequest;
use App\Models\User;
use Modules\Ticketing\Models\Ticket;
use Modules\Ticketing\Enums\TicketingPermission;

class TicketDatatableService
{
    public function getDatatable(DatatableRequest $request, User $user)
    {
        $query = Ticket::with(['user', 'assetItem.assetCategory']);

        // Permission-based filtering
        if ($user->can(TicketingPermission::ViewAllTicket->value)) {
            // See all tickets
        } elseif ($user->can(TicketingPermission::ViewDivisionTicket->value)) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('user', fn ($u) => $u->where('division_id', $user->division_id));
            });
        } else {
            $query->where('user_id', $user->id);
        }

        // Global search
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('subject', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%")
                  ->orWhereHas('assetItem', fn ($ai) =>
                      $ai->where('serial_number', 'like', "%{$request->search}%")
                         ->orWhereHas('assetCategory', fn ($ac) =>
                             $ac->where('name', 'like', "%{$request->search}%")
                         )
                  );
            });
        }

        // Year filter
        if ($year = $request->input('year')) {
            $query->whereYear('created_at', $year);
        }

        // Filters
        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->priority) {
            $query->where(function ($q) use ($request) {
                $q->where('priority', $request->priority)
                  ->orWhere('real_priority', $request->priority);
            });
        }

        if ($request->subject) {
            $query->where('subject', 'like', "%{$request->subject}%");
        }

        if ($request->user) {
            $query->whereHas('user', fn($q) => $q->where('name', 'like', "%{$request->user}%"));
        }

        if ($request->asset_item) {
            $query->whereHas('assetItem', function ($ai) use ($request) {
                $ai->where('serial_number', 'like', "%{$request->asset_item}%")
                   ->orWhere('merk', 'like', "%{$request->asset_item}%")
                   ->orWhere('model', 'like', "%{$request->asset_item}%")
                   ->orWhereHas('assetCategory', fn($ac) => $ac->where('name', 'like', "%{$request->asset_item}%"));
            });
        }

        $sortBy = $request->sort_by ?? 'created_at';
        $sortDirection = $request->sort_direction ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        return $query->paginate($request->limit ?? 10);
    }

    public function printExcel(DatatableRequest $request, User $loggedUser): mixed
    {
        $query = Ticket::with(['user', 'assetItem.assetCategory']);

        // Permission check
        if ($loggedUser->can(TicketingPermission::ViewAllTicket->value)) {
            // No filter
        } elseif ($loggedUser->can(TicketingPermission::ViewDivisionTicket->value)) {
            $query->where(function ($q) use ($loggedUser) {
                $q->where('user_id', $loggedUser->id)
                  ->orWhereHas('user', fn ($u) => $u->where('division_id', $loggedUser->division_id));
            });
        } else {
            $query->where('user_id', $loggedUser->id);
        }

        // Apply filters same as datatable
        if ($year = $request->input('year')) {
            $query->whereYear('created_at', $year);
        }
        
        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('subject', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%");
            });
        }

        $data = $query->orderBy('created_at', 'desc')->get();

        return response()->streamDownload(function () use ($data) {
            $writer = new \OpenSpout\Writer\XLSX\Writer;
            $writer->openToFile('php://output');

            // Header
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
                'No',
                'Tanggal',
                'Nama User',
                'Subject',
                'Asset',
                'Status',
                'Prioritas'
            ]));

            // Data
            foreach ($data as $index => $item) {
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
                    $index + 1,
                    $item->created_at->format('d/m/Y H:i'),
                    $item->user?->name ?? '-',
                    $item->subject,
                    $item->assetItem->assetCategory?->name . ' - ' . $item->assetItem->serial_number,
                    $item->status->label(),
                    $item->real_priority?->label() ?? $item->priority?->label() ?? '-'
                ]));
            }

            $writer->close();
        }, 'Data Tiket Per '.date('d F Y').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
