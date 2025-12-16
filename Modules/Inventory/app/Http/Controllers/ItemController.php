<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\DatatableRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Inventory\Datatables\ItemDatatableService;
use Modules\Inventory\Models\CategoryItem;
use Modules\Inventory\Models\Item;
use Modules\Inventory\Services\ItemService;
use Modules\Inventory\Services\StockConversionService;

class ItemController extends Controller
{
    public function __construct(
        private ItemService $itemService,
        private StockConversionService $conversionService,
        private ItemDatatableService $datatableService
    ) {}

    public function index()
    {
        return Inertia::render('Inventory/Item/Index', [
            'categories' => CategoryItem::where('is_active', true)->get(['id', 'name']),
        ]);
    }

    public function create()
    {
        // Get items that can be used as reference (items without reference - base units)
        $referenceItems = Item::whereNull('division_id')
            ->whereNull('reference_item_id')
            ->get(['id', 'name', 'unit_of_measure']);

        return Inertia::render('Inventory/Item/Create', [
            'categories' => CategoryItem::where('is_active', true)->get(['id', 'name']),
            'referenceItems' => $referenceItems,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:category_items,id',
            'unit_of_measure' => 'required|string|max:50',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string|max:500',
            'image_url' => 'nullable|string',
            'multiplier' => 'nullable|integer|min:1',
            'reference_item_id' => 'nullable|exists:items,id',
        ]);

        // Set default multiplier to 1 if not provided
        $validated['multiplier'] = $validated['multiplier'] ?? 1;

        Item::create($validated);

        return to_route('inventory.items.index')
            ->with('success', 'Barang berhasil ditambahkan.');
    }

    public function edit(Item $item)
    {
        // Get items that can be used as reference (items without reference - base units)
        // Exclude the current item to prevent self-reference
        $referenceItems = Item::whereNull('division_id')
            ->whereNull('reference_item_id')
            ->where('id', '!=', $item->id)
            ->get(['id', 'name', 'unit_of_measure']);

        return Inertia::render('Inventory/Item/Create', [
            'item' => $item->load(['category', 'referenceItem']),
            'categories' => CategoryItem::where('is_active', true)->get(['id', 'name']),
            'referenceItems' => $referenceItems,
        ]);
    }

    public function update(Request $request, Item $item)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:category_items,id',
            'unit_of_measure' => 'required|string|max:50',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string|max:500',
            'image_url' => 'nullable|string',
            'multiplier' => 'nullable|integer|min:1',
            'reference_item_id' => 'nullable|exists:items,id',
        ]);

        // Set default multiplier to 1 if not provided
        $validated['multiplier'] = $validated['multiplier'] ?? 1;

        $item->update($validated);

        return to_route('inventory.items.index')
            ->with('success', 'Barang berhasil diperbarui.');
    }

    public function delete(Item $item)
    {
        $item->delete();

        return to_route('inventory.items.index')
            ->with('success', 'Barang berhasil dihapus.');
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
        $targetItems = Item::where('reference_item_id', $item->id)
            ->orWhere(function ($query) use ($item) {
                $query->where('category_id', $item->category_id)
                    ->where('id', '!=', $item->id)
                    ->where('division_id', $item->division_id);
            })
            ->get(['id', 'name', 'unit_of_measure', 'stock', 'multiplier']);

        return Inertia::render('Inventory/Item/Convert', [
            'item' => $item->load('category'),
            'targetItems' => $targetItems,
        ]);
    }

    public function processConversion(Request $request, Item $item)
    {
        $validated = $request->validate([
            'quantity' => [
                'required',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) use ($item) {
                    if ($value > $item->stock) {
                        $fail('Jumlah konversi tidak boleh melebihi stok yang tersedia ('.$item->stock.').');
                    }
                },
            ],
        ]);

        try {
            $this->conversionService->convertStock($item, $validated['quantity'], $request->user());

            return to_route('inventory.items.index')
                ->with('success', 'Konversi stok berhasil dilakukan.');
        } catch (\Exception $e) {
            return back()->withErrors(['quantity' => $e->getMessage()]);
        }
    }

    public function issueForm(Item $item)
    {
        return Inertia::render('Inventory/Item/Issue', [
            'item' => $item->load('category'),
        ]);
    }

    public function issue(Request $request, Item $item)
    {
        $validated = $request->validate([
            'quantity' => [
                'required',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) use ($item) {
                    if ($value > $item->stock) {
                        $fail('Jumlah pengeluaran tidak boleh melebihi stok yang tersedia ('.$item->stock.').');
                    }
                },
            ],
            'description' => 'required|string|max:500',
        ]);

        try {
            $this->itemService->issueStock(
                $item,
                $validated['quantity'],
                $validated['description'],
                $request->user()
            );

            return to_route('inventory.items.index')
                ->with('success', 'Pengeluaran stok berhasil dilakukan.');
        } catch (\Exception $e) {
            return back()->withErrors(['quantity' => $e->getMessage()]);
        }
    }
}
