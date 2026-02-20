<?php

namespace Modules\Ticketing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\DatatableRequest;
use App\Models\Division;
use App\Models\User;
use Inertia\Inertia;
use Modules\Ticketing\Models\AssetItem;
use Modules\Ticketing\Models\AssetModel;
use Modules\Ticketing\Services\AssetItemService;
use Modules\Ticketing\Datatables\AssetItemDatatableService;
use Modules\Ticketing\Http\Requests\StoreAssetItemRequest;
use Modules\Ticketing\Http\Requests\UpdateAssetItemRequest;
use Modules\Ticketing\DataTransferObjects\AssetItemDTO;
use Illuminate\Support\Facades\Request;

class AssetItemController extends Controller
{
    public function __construct(
        private AssetItemService $assetItemService,
        private AssetItemDatatableService $datatableService,
    ) {}

    public function index()
    {
        return Inertia::render('Ticketing/AssetItem/Index');
    }

    public function create()
    {
        return Inertia::render('Ticketing/AssetItem/Create', [
            'assetModels' => AssetModel::all(['id', 'name', 'maintenance_count']),
            'divisions' => Division::all(['id', 'name']),
            // Initially empty users, or all users if you prefer
            'users' => User::all(['id', 'name', 'division_id']),
        ]);
    }

    public function store(StoreAssetItemRequest $request)
    {
        $dto = AssetItemDTO::fromRequest($request);
        $this->assetItemService->store($dto);

        return to_route('ticketing.assets.index')->with('success', 'Asset berhasil ditambahkan.');
    }

    public function edit(AssetItem $asset)
    {
        return Inertia::render('Ticketing/AssetItem/Edit', [
            'asset' => $asset->load(['assetModel', 'division', 'users']),
            'assetModels' => AssetModel::all(['id', 'name', 'maintenance_count']),
            'divisions' => Division::all(['id', 'name']),
            'users' => User::all(['id', 'name', 'division_id']),
        ]);
    }

    public function update(UpdateAssetItemRequest $request, AssetItem $asset)
    {
        $dto = AssetItemDTO::fromRequest($request);
        $this->assetItemService->update($asset->id, $dto);

        return to_route('ticketing.assets.index')->with('success', 'Asset berhasil diperbarui.');
    }

    public function delete(AssetItem $asset)
    {
        $this->assetItemService->delete($asset->id);

        return back()->with('success', 'Asset berhasil dihapus.');
    }

    public function datatable(DatatableRequest $request)
    {
        return $this->datatableService->getDatatable($request, auth()->user());
    }

    public function printExcel(DatatableRequest $request)
    {
        return $this->datatableService->printExcel($request, auth()->user());
    }

    public function getUsersByDivision($divisionId)
    {
        $users = User::where('division_id', $divisionId)->get(['id', 'name']);
        return response()->json($users);
    }
}
