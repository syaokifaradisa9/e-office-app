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
