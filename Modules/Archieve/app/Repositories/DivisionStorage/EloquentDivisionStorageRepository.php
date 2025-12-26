<?php

namespace Modules\Archieve\Repositories\DivisionStorage;

use Modules\Archieve\Models\DivisionStorage;

class EloquentDivisionStorageRepository implements DivisionStorageRepository
{
    public function all()
    {
        return DivisionStorage::with('division')->get();
    }

    public function find(int $id)
    {
        return DivisionStorage::findOrFail($id);
    }

    public function findByDivision(int $divisionId)
    {
        return DivisionStorage::where('division_id', $divisionId)->first();
    }

    public function findOrCreateByDivision(int $divisionId, array $defaults = []): DivisionStorage
    {
        return DivisionStorage::firstOrCreate(['division_id' => $divisionId], $defaults);
    }

    public function incrementUsedSize(int $divisionId, int $size): void
    {
        $storage = $this->findOrCreateByDivision($divisionId, ['max_size' => 0, 'used_size' => 0]);
        $storage->increment('used_size', $size);
    }

    public function decrementUsedSize(int $divisionId, int $size): void
    {
        $storage = $this->findByDivision($divisionId);
        if ($storage) {
            $storage->decrement('used_size', min($size, $storage->used_size));
        }
    }

    public function store(array $data)

    {
        return DivisionStorage::updateOrCreate(
            ['division_id' => $data['division_id']],
            $data
        );
    }

    public function update(DivisionStorage $storage, array $data)
    {
        $storage->update($data);
        return $storage;
    }

    public function delete(DivisionStorage $storage)
    {
        return $storage->delete();
    }
}
