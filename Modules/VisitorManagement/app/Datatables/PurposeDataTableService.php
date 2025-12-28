<?php

namespace Modules\VisitorManagement\Datatables;

use App\Http\Requests\DatatableRequest;
use Illuminate\Database\Eloquent\Builder;
use Modules\VisitorManagement\Models\VisitorPurpose;

class PurposeDataTableService
{
    public function getDatatable(DatatableRequest $request): mixed
    {
        $query = $this->getStartedQuery($request);

        return $query->paginate($request->limit ?? 20)
            ->withQueryString();
    }

    private function getStartedQuery(DatatableRequest $request): Builder
    {
        $query = VisitorPurpose::query();

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->get('name') . '%');
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->get('status') === 'active');
        }

        if ($request->has('sort_by') && $request->has('sort_direction')) {
            $query->orderBy($request->sort_by, $request->sort_direction);
        } else {
            $query->orderBy('name', 'asc');
        }

        return $query;
    }
}
