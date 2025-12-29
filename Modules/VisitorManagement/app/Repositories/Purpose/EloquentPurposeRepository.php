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

    public function getDatatableQuery(array $params): \Illuminate\Database\Eloquent\Builder
    {
        $query = VisitorPurpose::query();

        if (isset($params['search']) && !empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (isset($params['name']) && !empty($params['name'])) {
            $query->where('name', 'like', '%' . $params['name'] . '%');
        }

        if (isset($params['status']) && !empty($params['status'])) {
            $query->where('is_active', $params['status'] === 'active');
        }

        if (isset($params['sort_by']) && isset($params['sort_direction'])) {
            $query->orderBy($params['sort_by'], $params['sort_direction']);
        } else {
            $query->orderBy('name', 'asc');
        }

        return $query;
    }
}
