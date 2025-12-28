<?php

namespace Modules\VisitorManagement\Datatables;

use App\Http\Requests\DatatableRequest;
use Illuminate\Database\Eloquent\Builder;
use Modules\VisitorManagement\Models\VisitorFeedbackQuestion;

class FeedbackQuestionDataTableService
{
    public function getDatatable(DatatableRequest $request): mixed
    {
        $query = $this->getStartedQuery($request);

        return $query->paginate($request->limit ?? 20)
            ->withQueryString();
    }

    private function getStartedQuery(DatatableRequest $request): Builder
    {
        $query = VisitorFeedbackQuestion::query();

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where('question', 'like', "%{$search}%");
        }

        if ($request->filled('question')) {
            $query->where('question', 'like', '%' . $request->get('question') . '%');
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->get('status') === 'active');
        }

        if ($request->has('sort_by') && $request->has('sort_direction')) {
            $query->orderBy($request->sort_by, $request->sort_direction);
        } else {
            $query->orderBy('id', 'desc');
        }

        return $query;
    }
}
