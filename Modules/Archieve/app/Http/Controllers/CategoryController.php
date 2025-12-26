<?php

namespace Modules\Archieve\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\DatatableRequest;
use Inertia\Inertia;
use Modules\Archieve\Datatables\CategoryDatatableService;
use Modules\Archieve\DataTransferObjects\StoreCategoryDTO;
use Modules\Archieve\Http\Requests\StoreCategoryRequest;
use Modules\Archieve\Models\Category;
use Modules\Archieve\Services\CategoryService;
use Modules\Archieve\Services\CategoryContextService;
use Modules\Archieve\Enums\ArchievePermission;

use Illuminate\Support\Facades\Gate;

class CategoryController extends Controller
{
    public function __construct(
        private CategoryService $categoryService,
        private CategoryDatatableService $datatableService,
        private CategoryContextService $contextService
    ) {}

    public function index()
    {
        Gate::authorize(ArchievePermission::ViewCategory->value);

        return Inertia::render('Archieve/Category/Index', [
            'contexts' => $this->contextService->all(),
        ]);
    }

    public function create()
    {
        Gate::authorize(ArchievePermission::ManageCategory->value);

        return Inertia::render('Archieve/Category/Create', [
            'contexts' => $this->contextService->all(),
        ]);
    }


    public function store(StoreCategoryRequest $request)
    {
        $dto = StoreCategoryDTO::fromRequest($request);
        $this->categoryService->store($dto);

        return to_route('archieve.categories.index')
            ->with('success', 'Kategori arsip berhasil ditambahkan.');
    }

    public function edit(Category $category)
    {
        Gate::authorize(ArchievePermission::ManageCategory->value);

        return Inertia::render('Archieve/Category/Create', [
            'category' => $category,
            'contexts' => $this->contextService->all(),
        ]);
    }


    public function update(StoreCategoryRequest $request, Category $category)
    {
        $dto = StoreCategoryDTO::fromRequest($request);
        $this->categoryService->update($category, $dto);

        return to_route('archieve.categories.index')
            ->with('success', 'Kategori arsip berhasil diperbarui.');
    }

    public function destroy(Category $category)
    {
        Gate::authorize(ArchievePermission::ManageCategory->value);

        $this->categoryService->delete($category);

        return to_route('archieve.categories.index')
            ->with('success', 'Kategori arsip berhasil dihapus.');
    }

    public function datatable(DatatableRequest $request)
    {
        Gate::authorize(ArchievePermission::ViewCategory->value);

        return $this->datatableService->getDatatable($request, $request->user());
    }

    public function printExcel(DatatableRequest $request)
    {
        Gate::authorize(ArchievePermission::ViewCategory->value);

        return $this->datatableService->printExcel($request, $request->user());
    }
}
