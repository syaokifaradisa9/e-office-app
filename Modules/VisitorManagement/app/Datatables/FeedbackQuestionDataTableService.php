<?php

namespace Modules\VisitorManagement\Datatables;

use App\Http\Requests\DatatableRequest;
use Illuminate\Database\Eloquent\Builder;
use Modules\VisitorManagement\Repositories\FeedbackQuestion\FeedbackQuestionRepository;

class FeedbackQuestionDataTableService
{
    public function __construct(
        private FeedbackQuestionRepository $feedbackQuestionRepository
    ) {}

    public function getDatatable(DatatableRequest $request): mixed
    {
        return $this->feedbackQuestionRepository->getDatatableQuery($request->all())
            ->paginate($request->limit ?? 20)
            ->withQueryString();
    }
}
