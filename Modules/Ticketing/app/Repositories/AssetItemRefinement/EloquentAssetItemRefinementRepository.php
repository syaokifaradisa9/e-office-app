<?php

namespace Modules\Ticketing\Repositories\AssetItemRefinement;

use Modules\Ticketing\Models\AssetItemRefinement;

class EloquentAssetItemRefinementRepository implements AssetItemRefinementRepository
{
    public function store(array $data): AssetItemRefinement
    {
        return AssetItemRefinement::create($data);
    }

    public function getByMaintenanceId(int $maintenanceId)
    {
        return AssetItemRefinement::where('maintenance_id', $maintenanceId)
            ->orderBy('date', 'desc')
            ->get();
    }

    public function delete(int $id): bool
    {
        $item = AssetItemRefinement::find($id);
        return $item ? $item->delete() : false;
    }

    public function findById(int $id): ?AssetItemRefinement
    {
        return AssetItemRefinement::find($id);
    }

    public function update(int $id, array $data): bool
    {
        $item = AssetItemRefinement::find($id);
        return $item ? $item->update($data) : false;
    }
}
