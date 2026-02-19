<?php

namespace Modules\Ticketing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\DatatableRequest;
use Inertia\Inertia;
use Modules\Ticketing\Models\AssetModel;
use Modules\Ticketing\Services\AssetModelService;
use Modules\Ticketing\Datatables\AssetModelDatatableService;
use Modules\Ticketing\Http\Requests\StoreAssetModelRequest;
use Modules\Ticketing\Http\Requests\UpdateAssetModelRequest;
use Modules\Ticketing\DataTransferObjects\AssetModelDTO;
use Modules\Inventory\Services\LookupService;

class AssetModelController extends Controller
{
    public function __construct(
        private AssetModelService $assetModelService,
        private AssetModelDatatableService $datatableService,
        private LookupService $lookupService
    ) {}

    public function index()
    {
        return Inertia::render('Ticketing/AssetModel/Index');
    }

    public function create()
    {
        return Inertia::render('Ticketing/AssetModel/Create', [
            'divisions' => $this->lookupService->getActiveDivisions(),
        ]);
    }

    public function store(StoreAssetModelRequest $request)
    {
        $dto = AssetModelDTO::fromRequest($request);
        $this->assetModelService->store($dto);

        return to_route('ticketing.asset-models.index')
            ->with('success', 'Data Asset Model berhasil ditambahkan.');
    }

    public function edit(AssetModel $assetModel)
    {
        return Inertia::render('Ticketing/AssetModel/Create', [
            'assetModel' => $assetModel,
            'divisions' => $this->lookupService->getActiveDivisions(),
        ]);
    }

    public function update(UpdateAssetModelRequest $request, $id)
    {
        $dto = AssetModelDTO::fromRequest($request);
        $this->assetModelService->update((int) $id, $dto);

        return to_route('ticketing.asset-models.index')
            ->with('success', 'Data Asset Model berhasil diperbarui.');
    }

    public function delete($id)
    {
        $this->assetModelService->delete((int) $id);

        return to_route('ticketing.asset-models.index')
            ->with('success', 'Data Asset Model berhasil dihapus.');
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
