<?php

namespace Modules\Ticketing\Repositories\AssetCategory;

use Modules\Ticketing\Models\AssetCategory;
use Illuminate\Database\Eloquent\Collection;

class EloquentAssetCategoryRepository implements AssetCategoryRepository
{
    public function getAll(array $filters = []): Collection
    {
        $query = AssetCategory::query()->with('division');

        if (isset($filters['division_id'])) {
            $query->where('division_id', $filters['division_id']);
        }

        return $query->get();
    }

    public function findById(int $id): ?AssetCategory
    {
        return AssetCategory::with('division')->find($id);
    }

    public function store(array $data): AssetCategory
    {
        return AssetCategory::create($data);
    }

    public function update(int $id, array $data): bool
    {
        return AssetCategory::where('id', $id)->update($data);
    }

    public function delete(int $id): bool
    {
        return AssetCategory::where('id', $id)->delete();
    }
}
