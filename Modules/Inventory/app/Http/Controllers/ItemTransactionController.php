<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\DatatableRequest;
use App\Models\Division;
use Inertia\Inertia;
use Modules\Inventory\Datatables\ItemTransactionDatatableService;
use Modules\Inventory\Enums\ItemTransactionType;

class ItemTransactionController extends Controller
{
    public function __construct(
        private ItemTransactionDatatableService $datatableService
    ) {}

    public function index()
    {
        return Inertia::render('Inventory/ItemTransaction/Index', [
            'divisions' => Division::where('is_active', true)->get(['id', 'name']),
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
