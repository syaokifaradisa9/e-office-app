<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\DatatableRequest;
use App\Models\Division;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Inventory\Datatables\StockMonitoringDatatableService;
use Modules\Inventory\Models\CategoryItem;
use Modules\Inventory\Models\Item;
use Modules\Inventory\Services\ItemService;
use Modules\Inventory\Services\StockConversionService;

class StockMonitoringController extends Controller
{
    public function __construct(
        private StockMonitoringDatatableService $datatableService,
        private StockConversionService $stockConversionService,
        private ItemService $itemService
    ) {}

    public function index()
    {
        return Inertia::render('Inventory/StockMonitoring/Index', [
            'categories' => CategoryItem::where('is_active', true)->get(),
            'divisions' => Division::all(),
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

    public function processConversion(Request $request, Item $item)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1|max:'.$item->stock,
        ]);

        try {
            $this->stockConversionService->convertStock($item, $request->quantity, $request->user());

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

    public function issue(Request $request, Item $item)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1|max:'.$item->stock,
            'description' => 'required|string|max:255',
        ]);

        try {
            $this->itemService->issueStock($item, $request->quantity, $request->description, $request->user());

            return redirect()->route('inventory.stock-monitoring.index')->with('success', 'Barang berhasil dikeluarkan.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
