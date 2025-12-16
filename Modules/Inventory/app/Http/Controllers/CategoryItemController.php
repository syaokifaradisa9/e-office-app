<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\DatatableRequest;
use Inertia\Inertia;
use Modules\Inventory\Datatables\CategoryItemDatatableService;
use Modules\Inventory\DataTransferObjects\StoreCategoryItemDTO;
use Modules\Inventory\DataTransferObjects\UpdateCategoryItemDTO;
use Modules\Inventory\Http\Requests\StoreCategoryItemRequest;
use Modules\Inventory\Http\Requests\UpdateCategoryItemRequest;
use Modules\Inventory\Models\CategoryItem;
use Modules\Inventory\Services\CategoryItemService;

class CategoryItemController extends Controller
{
    public function __construct(
        private CategoryItemService $categoryItemService,
        private CategoryItemDatatableService $datatableService
    ) {}

    public function index()
    {
        return Inertia::render('Inventory/CategoryItem/Index');
    }

    public function create()
    {
        return Inertia::render('Inventory/CategoryItem/Create');
    }

    public function store(StoreCategoryItemRequest $request)
    {
        $dto = StoreCategoryItemDTO::fromRequest($request);
        $this->categoryItemService->store($dto);

        return to_route('inventory.categories.index')
            ->with('success', 'Kategori barang berhasil ditambahkan.');
    }

    public function edit(CategoryItem $categoryItem)
    {
        return Inertia::render('Inventory/CategoryItem/Create', [
            'categoryItem' => $categoryItem,
        ]);
    }

    public function update(UpdateCategoryItemRequest $request, CategoryItem $categoryItem)
    {
        $dto = UpdateCategoryItemDTO::fromRequest($request);
        $this->categoryItemService->update($categoryItem, $dto);

        return to_route('inventory.categories.index')
            ->with('success', 'Kategori barang berhasil diperbarui.');
    }

    public function delete(CategoryItem $categoryItem)
    {
        // Check if category has items linked to it
        if ($categoryItem->items()->exists()) {
            return back()->withErrors(['delete' => 'Kategori tidak bisa dihapus karena masih digunakan oleh barang.']);
        }

        $this->categoryItemService->delete($categoryItem);

        return to_route('inventory.categories.index')
            ->with('success', 'Kategori barang berhasil dihapus.');
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
