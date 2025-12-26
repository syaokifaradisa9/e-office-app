<?php

namespace App\Repositories\User;

use App\Models\User;
use App\Repositories\EloquentBaseRepository;
use Illuminate\Database\Eloquent\Collection;

class EloquentUserRepository extends EloquentBaseRepository implements UserRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    /**
     * Get users by division
     */
    public function getByDivision(int $divisionId): Collection
    {
        return $this->model->where('division_id', $divisionId)->orderBy('name')->get();
    }

    /**
     * Get users by divisions
     */
    public function getByDivisions(array $divisionIds): Collection
    {
        return $this->model->whereIn('division_id', $divisionIds)->orderBy('name')->get();
    }

    /**
     * Get users by division with specific columns
     */
    public function getByDivisionWithColumns(int $divisionId, array $columns = ['id', 'name', 'division_id']): Collection
    {
        return $this->model->where('division_id', $divisionId)->orderBy('name')->get($columns);
    }
}

