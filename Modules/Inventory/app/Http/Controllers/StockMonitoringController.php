<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\DatatableRequest;
use Inertia\Inertia;
use Modules\Inventory\Datatables\StockMonitoringDatatableService;
use Modules\Inventory\Http\Requests\ConvertItemRequest;
use Modules\Inventory\Http\Requests\IssueItemRequest;
use Modules\Inventory\Models\Item;
use Modules\Inventory\Services\ItemService;
use Modules\Inventory\Services\LookupService;
use Modules\Inventory\Services\StockConversionService;

class StockMonitoringController extends Controller
{
    public function __construct(
        private StockMonitoringDatatableService $datatableService,
        private StockConversionService $stockConversionService,
        private ItemService $itemService,
        private LookupService $lookupService
    ) {}

    public function index()
    {
        return Inertia::render('Inventory/StockMonitoring/Index', [
            'categories' => $this->lookupService->getActiveCategories(),
            'divisions' => $this->lookupService->getActiveDivisions(),
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

    public function convert(Item $item)
    {
        // Security check: ensure user can only access their division's item
        if ($item->division_id != auth()->user()->division_id) {
            abort(403, 'Unauthorized action.');
        }

        if ($item->multiplier <= 1) {
            return redirect()->route('inventory.stock-monitoring.index')->with('error', 'Item ini tidak dapat dikonversi.');
        }

        return Inertia::render('Inventory/StockMonitoring/Convert', [
            'item' => $item->load(['referenceItem', 'mainReferenceItem.referenceItem']),
        ]);
    }

    public function processConversion(ConvertItemRequest $request, Item $item)
    {
        $validated = $request->validated();

        try {
            $this->stockConversionService->convertStock($item, $validated['quantity'], $request->user());

            return redirect()->route('inventory.stock-monitoring.index')->with('success', 'Stok berhasil dikonversi.');
        } catch (\Exception $e) {
            return back()->withErrors(['quantity' => $e->getMessage()]);
        }
    }

    public function issueForm(Item $item)
    {
        if ($item->stock <= 0) {
            return redirect()->route('inventory.stock-monitoring.index')->with('error', 'Stok barang habis.');
        }

        return Inertia::render('Inventory/StockMonitoring/Issue', [
            'item' => $item,
            'backPath' => route('inventory.stock-monitoring.index'),
        ]);
    }

    public function issue(IssueItemRequest $request, Item $item)
    {
        $validated = $request->validated();

        try {
            $this->itemService->issueStock($item, $validated['quantity'], $validated['description'], $request->user());

            return redirect()->route('inventory.stock-monitoring.index')->with('success', 'Barang berhasil dikeluarkan.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
