<?php

namespace Modules\VisitorManagement\Repositories\Purpose;

use Modules\VisitorManagement\Models\VisitorPurpose;
use Illuminate\Database\Eloquent\Collection;

class EloquentPurposeRepository implements PurposeRepository
{
    public function all(): Collection
    {
        return VisitorPurpose::all();
    }

    public function findById(int $id): ?VisitorPurpose
    {
        return VisitorPurpose::find($id);
    }

    public function create(array $data): VisitorPurpose
    {
        return VisitorPurpose::create($data);
    }

    public function update(VisitorPurpose $purpose, array $data): bool
    {
        return $purpose->update($data);
    }

    public function delete(VisitorPurpose $purpose): bool
    {
        return $purpose->delete();
    }

    public function hasVisitors(VisitorPurpose $purpose): bool
    {
        return $purpose->visitors()->exists();
    }
}
