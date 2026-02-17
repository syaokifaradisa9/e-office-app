<?php

namespace Modules\VisitorManagement\Datatables;

use App\Http\Requests\DatatableRequest;
use Illuminate\Database\Eloquent\Builder;
use Modules\VisitorManagement\Repositories\Purpose\PurposeRepository;

class PurposeDataTableService
{
    public function __construct(
        private PurposeRepository $purposeRepository
    ) {}

    public function getDatatable(DatatableRequest $request): mixed
    {
        return $this->purposeRepository->getDatatableQuery($request->all())
            ->paginate($request->limit ?? 20)
            ->withQueryString();
    }
}
