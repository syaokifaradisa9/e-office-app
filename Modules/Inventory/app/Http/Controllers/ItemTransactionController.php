<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\DatatableRequest;
use Inertia\Inertia;
use Modules\Inventory\Datatables\ItemTransactionDatatableService;
use Modules\Inventory\Enums\ItemTransactionType;
use Modules\Inventory\Services\LookupService;

class ItemTransactionController extends Controller
{
    public function __construct(
        private ItemTransactionDatatableService $datatableService,
        private LookupService $lookupService
    ) {}

    public function index()
    {
        return Inertia::render('Inventory/ItemTransaction/Index', [
            'divisions' => $this->lookupService->getActiveDivisions(),
            'transactionTypes' => collect(ItemTransactionType::cases())->map(fn ($type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ]),
        ]);
    }

    public function datatable(DatatableRequest $request)
    {
        return $this->datatableService->getDatatable($request, $request->user());
    }

    public function printExcel(DatatableRequest $request)
    {
        return $this->datatableService->printExcel($request, $request->user());
    }
}
