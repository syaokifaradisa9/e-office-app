<?php

namespace Modules\Archieve\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\DatatableRequest;
use Inertia\Inertia;
use Modules\Archieve\Datatables\CategoryContextDatatableService;
use Modules\Archieve\DataTransferObjects\StoreCategoryContextDTO;
use Modules\Archieve\Http\Requests\StoreCategoryContextRequest;
use Modules\Archieve\Models\CategoryContext;
use Modules\Archieve\Services\CategoryContextService;
use Modules\Archieve\Enums\ArchieveUserPermission;
use Illuminate\Support\Facades\Gate;

class CategoryContextController extends Controller
{
    public function __construct(
        private CategoryContextService $contextService,
        private CategoryContextDatatableService $datatableService
    ) {}

    public function index()
    {
        Gate::authorize(ArchieveUserPermission::ViewCategory->value);

        return Inertia::render('Archieve/Context/Index');
    }

    public function create()
    {
        Gate::authorize(ArchieveUserPermission::ManageCategory->value);

        return Inertia::render('Archieve/Context/Create');
    }

    public function store(StoreCategoryContextRequest $request)
    {
        $dto = StoreCategoryContextDTO::fromRequest($request);
        $this->contextService->store($dto);

        return to_route('archieve.contexts.index')
            ->with('success', 'Konteks arsip berhasil ditambahkan.');
    }

    public function edit(CategoryContext $context)
    {
        Gate::authorize(ArchieveUserPermission::ManageCategory->value);

        return Inertia::render('Archieve/Context/Create', [
            'context' => $context,
        ]);
    }

    public function update(StoreCategoryContextRequest $request, CategoryContext $context)
    {
        $dto = StoreCategoryContextDTO::fromRequest($request);
        $this->contextService->update($context, $dto);

        return to_route('archieve.contexts.index')
            ->with('success', 'Konteks arsip berhasil diperbarui.');
    }

    public function destroy(CategoryContext $context)
    {
        Gate::authorize(ArchieveUserPermission::ManageCategory->value);

        $this->contextService->delete($context);

        return to_route('archieve.contexts.index')
            ->with('success', 'Konteks arsip berhasil dihapus.');
    }

    public function datatable(DatatableRequest $request)
    {
        Gate::authorize(ArchieveUserPermission::ViewCategory->value);

        return $this->datatableService->getDatatable($request, $request->user());
    }

    public function printExcel(DatatableRequest $request, $type)
    {
        Gate::authorize(ArchieveUserPermission::ViewCategory->value);

        return $this->datatableService->printExcel($request, $request->user());
    }
}
