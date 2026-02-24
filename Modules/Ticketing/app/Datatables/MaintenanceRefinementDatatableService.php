<?php

namespace Modules\Ticketing\Datatables;

use App\Http\Requests\DatatableRequest;
use Modules\Ticketing\Repositories\AssetItemRefinement\AssetItemRefinementRepository;

class MaintenanceRefinementDatatableService
{
    public function __construct(
        private AssetItemRefinementRepository $repository
    ) {}

    public function getDatatable(DatatableRequest $request, int $maintenanceId)
    {
        $query = \Modules\Ticketing\Models\AssetItemRefinement::where('maintenance_id', $maintenanceId);

        if ($request->search) {
            $query->where(function($q) use ($request) {
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
            if (strlen($request->date) === 7) { // YYYY-MM
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
