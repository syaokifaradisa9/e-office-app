<?php

namespace Modules\Ticketing\Repositories\AssetModel;

use Modules\Ticketing\Models\AssetModel;
use Illuminate\Database\Eloquent\Collection;

class EloquentAssetModelRepository implements AssetModelRepository
{
    public function getAll(array $filters = []): Collection
    {
        $query = AssetModel::query()->with('division');

        if (isset($filters['division_id'])) {
            $query->where('division_id', $filters['division_id']);
        }

        return $query->get();
    }

    public function findById(int $id): ?AssetModel
    {
        return AssetModel::with('division')->find($id);
    }

    public function store(array $data): AssetModel
    {
        return AssetModel::create($data);
    }

    public function update(int $id, array $data): bool
    {
        return AssetModel::where('id', $id)->update($data);
    }

    public function delete(int $id): bool
    {
        return AssetModel::where('id', $id)->delete();
    }
}
