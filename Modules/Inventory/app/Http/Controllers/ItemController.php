<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\DatatableRequest;
use Inertia\Inertia;
use Modules\Inventory\DataTransferObjects\ItemDTO;
use Modules\Inventory\Datatables\ItemDatatableService;
use Modules\Inventory\Http\Requests\ConvertItemRequest;
use Modules\Inventory\Http\Requests\IssueItemRequest;
use Modules\Inventory\Http\Requests\StoreItemRequest;
use Modules\Inventory\Http\Requests\UpdateItemRequest;
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
            'categories' => $this->itemService->getActiveCategories(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Inventory/Item/Create', [
            'categories' => $this->itemService->getActiveCategories(),
            'referenceItems' => $this->itemService->getBaseUnits(),
        ]);
    }

    public function store(StoreItemRequest $request)
    {
        $dto = ItemDTO::fromRequest($request);
        $this->itemService->store($dto);

        return to_route('inventory.items.index')
            ->with('success', 'Barang berhasil ditambahkan.');
    }

    public function edit(Item $item)
    {
        return Inertia::render('Inventory/Item/Create', [
            'item' => $item->load(['category', 'referenceItem']),
            'categories' => $this->itemService->getActiveCategories(),
            'referenceItems' => $this->itemService->getBaseUnits(),
        ]);
    }

    public function update(UpdateItemRequest $request, Item $item)
    {
        $dto = ItemDTO::fromRequest($request);
        $this->itemService->update($item, $dto);

        return to_route('inventory.items.index')
            ->with('success', 'Barang berhasil diperbarui.');
    }

    public function delete(Item $item)
    {
        $this->itemService->delete($item);

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
        return Inertia::render('Inventory/Item/Convert', [
            'item' => $item->load('category'),
            'targetItems' => $this->itemService->getConversionTargets($item),
        ]);
    }

    public function processConversion(ConvertItemRequest $request, Item $item)
    {
        $validated = $request->validated();

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

    public function issue(IssueItemRequest $request, Item $item)
    {
        $validated = $request->validated();

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
