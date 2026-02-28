<?php

namespace Modules\Ticketing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\DatatableRequest;
use App\Models\Division;
use App\Models\User;
use Inertia\Inertia;
use Modules\Ticketing\Models\AssetItem;
use Modules\Ticketing\Models\AssetCategory;
use Modules\Ticketing\Services\AssetItemService;
use Modules\Ticketing\Datatables\AssetItemDatatableService;
use Modules\Ticketing\Http\Requests\StoreAssetItemRequest;
use Modules\Ticketing\Http\Requests\UpdateAssetItemRequest;
use Modules\Ticketing\DataTransferObjects\AssetItemDTO;
use Modules\Ticketing\Enums\TicketingPermission;
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
            'assetCategories' => AssetCategory::all(['id', 'name', 'maintenance_count']),
            'divisions' => Division::all(['id', 'name']),
            // Initially empty users, or all users if you prefer
            'users' => User::all(['id', 'name', 'division_id']),
        ]);
    }

    public function store(StoreAssetItemRequest $request)
    {
        $dto = AssetItemDTO::fromRequest($request);
        $dto = $this->enforceOwnership($dto);
        $this->assetItemService->store($dto);

        return to_route('ticketing.assets.index')->with('success', 'Asset berhasil ditambahkan.');
    }

    public function edit(AssetItem $asset)
    {
        return Inertia::render('Ticketing/AssetItem/Edit', [
            'asset' => $asset->load(['assetCategory', 'division', 'users']),
            'assetCategories' => AssetCategory::all(['id', 'name', 'maintenance_count']),
            'divisions' => Division::all(['id', 'name']),
            'users' => User::all(['id', 'name', 'division_id']),
        ]);
    }

    public function update(UpdateAssetItemRequest $request, AssetItem $asset)
    {
        $dto = AssetItemDTO::fromRequest($request);
        $dto = $this->enforceOwnership($dto);
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

    /**
     * Enforce ownership rules based on user permission level.
     * - Personal: force own division_id & user_id
     * - Division: force own division_id, filter user_ids to same division
     * - All: allow any division_id, filter user_ids to chosen division
     */
    private function enforceOwnership(AssetItemDTO $dto): AssetItemDTO
    {
        $user = auth()->user();

        $isAll = $user->can(TicketingPermission::ViewAllAsset->value);
        $isDivision = $user->can(TicketingPermission::ViewDivisionAsset->value);

        if ($isAll) {
            // All: bebas pilih division_id, tapi user_ids harus satu divisi dengan division_id pilihan
            $filteredUserIds = User::whereIn('id', $dto->user_ids)
                ->where('division_id', $dto->division_id)
                ->pluck('id')->toArray();

            return new AssetItemDTO(
                asset_category_id: $dto->asset_category_id,
                merk: $dto->merk,
                model: $dto->model,
                serial_number: $dto->serial_number,
                division_id: $dto->division_id,
                another_attributes: $dto->another_attributes,
                user_ids: $filteredUserIds,
                last_maintenance_date: $dto->last_maintenance_date,
            );
        }

        if ($isDivision) {
            // Division: paksa division_id sendiri, filter user_ids ke divisi sendiri
            $filteredUserIds = User::whereIn('id', $dto->user_ids)
                ->where('division_id', $user->division_id)
                ->pluck('id')->toArray();

            return new AssetItemDTO(
                asset_category_id: $dto->asset_category_id,
                merk: $dto->merk,
                model: $dto->model,
                serial_number: $dto->serial_number,
                division_id: $user->division_id,
                another_attributes: $dto->another_attributes,
                user_ids: $filteredUserIds,
                last_maintenance_date: $dto->last_maintenance_date,
            );
        }

        // Personal / Default: paksa division_id & user_ids ke user login sendiri
        return new AssetItemDTO(
            asset_category_id: $dto->asset_category_id,
            merk: $dto->merk,
            model: $dto->model,
            serial_number: $dto->serial_number,
            division_id: $user->division_id,
            another_attributes: $dto->another_attributes,
            user_ids: [$user->id],
            last_maintenance_date: $dto->last_maintenance_date,
        );
    }
}
