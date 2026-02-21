<?php

namespace Modules\Ticketing\Repositories\AssetItem;

use Modules\Ticketing\DataTransferObjects\AssetItemDTO;
use Modules\Ticketing\Models\AssetItem;

class EloquentAssetItemRepository implements AssetItemRepository
{
    public function store(AssetItemDTO $dto): AssetItem
    {
        $asset = AssetItem::create($dto->toArray());
        $asset->users()->sync($dto->user_ids);
        return $asset;
    }

    public function update(int $id, AssetItemDTO $dto): bool
    {
        $assetItem = AssetItem::findOrFail($id);
        $updated = $assetItem->update($dto->toArray());
        if ($updated) {
            $assetItem->users()->sync($dto->user_ids);
        }
        return $updated;
    }

    public function delete(int $id): bool
    {
        $assetItem = AssetItem::findOrFail($id);
        return $assetItem->delete();
    }

    public function findById(int $id): ?AssetItem
    {
        return AssetItem::with(['assetCategory', 'division', 'users'])->find($id);
    }
}
