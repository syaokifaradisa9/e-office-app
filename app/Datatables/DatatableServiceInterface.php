<?php

namespace App\Datatables;

use App\Http\Requests\DatatableRequest;
use App\Models\User;

interface DatatableServiceInterface
{
    public function getDatatable(DatatableRequest $request, User $loggedUser): mixed;

    public function printExcel(DatatableRequest $request, User $loggedUser): mixed;
}
