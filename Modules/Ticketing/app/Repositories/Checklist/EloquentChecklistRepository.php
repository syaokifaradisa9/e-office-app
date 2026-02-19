<?php

namespace Modules\Ticketing\Repositories\Checklist;

use Modules\Ticketing\Models\Checklist;
use Illuminate\Database\Eloquent\Collection;

class EloquentChecklistRepository implements ChecklistRepository
{
    public function getAllByAssetModelId(int $assetModelId): Collection
    {
        return Checklist::where('asset_model_id', $assetModelId)
            ->orderBy('label')
            ->get();
    }

    public function findById(int $id): ?Checklist
    {
        return Checklist::find($id);
    }

    public function store(array $data): Checklist
    {
        return Checklist::create($data);
    }

    public function update(int $id, array $data): bool
    {
        return Checklist::where('id', $id)->update($data);
    }

    public function delete(int $id): bool
    {
        return Checklist::where('id', $id)->delete();
    }
}
