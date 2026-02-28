<?php

namespace Modules\Ticketing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\DatatableRequest;
use Inertia\Inertia;
use Modules\Ticketing\Models\AssetCategory;
use Modules\Ticketing\Services\AssetCategoryService;
use Modules\Ticketing\Datatables\AssetCategoryDatatableService;
use Modules\Ticketing\Http\Requests\StoreAssetCategoryRequest;
use Modules\Ticketing\Http\Requests\UpdateAssetCategoryRequest;
use Modules\Ticketing\DataTransferObjects\AssetCategoryDTO;
use Modules\Inventory\Services\LookupService;

class AssetCategoryController extends Controller
{
    public function __construct(
        private AssetCategoryService $assetCategoryService,
        private AssetCategoryDatatableService $datatableService,
        private LookupService $lookupService
    ) {}

    public function index()
    {
        return Inertia::render('Ticketing/AssetCategory/Index');
    }

    public function create()
    {
        return Inertia::render('Ticketing/AssetCategory/Create', [
            'divisions' => $this->lookupService->getActiveDivisions(),
        ]);
    }

    public function store(StoreAssetCategoryRequest $request)
    {
        $dto = AssetCategoryDTO::fromRequest($request);
        $this->assetCategoryService->store($dto);

        return to_route('ticketing.asset-categories.index')
            ->with('success', 'Data Kategori Asset berhasil ditambahkan.');
    }

    public function edit(AssetCategory $assetCategory)
    {
        return Inertia::render('Ticketing/AssetCategory/Create', [
            'assetCategory' => $assetCategory,
            'divisions' => $this->lookupService->getActiveDivisions(),
        ]);
    }

    public function update(UpdateAssetCategoryRequest $request, $id)
    {
        $dto = AssetCategoryDTO::fromRequest($request);
        $this->assetCategoryService->update((int) $id, $dto);

        return to_route('ticketing.asset-categories.index')
            ->with('success', 'Data Kategori Asset berhasil diperbarui.');
    }

    public function delete($id)
    {
        $this->assetCategoryService->delete((int) $id);

        return to_route('ticketing.asset-categories.index')
            ->with('success', 'Data Kategori Asset berhasil dihapus.');
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
