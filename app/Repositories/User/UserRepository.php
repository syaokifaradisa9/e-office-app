<?php

namespace App\Repositories\User;

use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

interface UserRepository extends BaseRepository
{
    /**
     * Get users by division
     */
    public function getByDivision(int $divisionId): Collection;

    /**
     * Get users by divisions
     */
    public function getByDivisions(array $divisionIds): Collection;

    /**
     * Get users by division with specific columns
     */
    public function getByDivisionWithColumns(int $divisionId, array $columns = ['id', 'name', 'division_id']): Collection;
}

