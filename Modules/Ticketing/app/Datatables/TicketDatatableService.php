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
}
