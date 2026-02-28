<?php

namespace Modules\Ticketing\Datatables;

use App\Http\Requests\DatatableRequest;
use Modules\Ticketing\Models\AssetItemRefinement;

class TicketRefinementDatatableService
{
    public function getDatatable(DatatableRequest $request, int $ticketId)
    {
        $query = AssetItemRefinement::where('ticket_id', $ticketId);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('description', 'like', "%{$request->search}%")
                  ->orWhere('result', 'like', "%{$request->search}%")
                  ->orWhere('note', 'like', "%{$request->search}%");
            });
        }

        if ($request->description) {
            $query->where('description', 'like', "%{$request->description}%");
        }

        if ($request->result) {
            $query->where('result', 'like', "%{$request->result}%");
        }

        if ($request->note) {
            $query->where('note', 'like', "%{$request->note}%");
        }

        if ($request->date) {
            if (strlen($request->date) === 7) {
                $query->where('date', 'like', "{$request->date}%");
            } else {
                $query->whereDate('date', $request->date);
            }
        }

        $sortBy = $request->sort_by ?? 'date';
        $sortDirection = $request->sort_direction ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        return $query->paginate($request->limit ?? 10);
    }
}
