<?php

namespace App\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface BaseRepository
{
    /**
     * Get all records
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * Find a record by ID
     */
    public function find(int|string $id, array $columns = ['*']): ?Model;

    /**
     * Create a new record
     */
    public function create(array $attributes): Model;

    /**
     * Update a record
     */
    public function update(Model $model, array $attributes): Model;

    /**
     * Delete a record
     */
    public function delete(Model $model): bool;

    /**
     * Find a record by ID or fail
     *
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int|string $id, array $columns = ['*']): Model;

    /**
     * Find records by criteria
     */
    public function findBy(array $criteria, array $columns = ['*']): Collection;

    /**
     * Get paginated records
     *
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $columns = ['*']);

    /**
     * Find a record by ID with relationships
     */
    public function findWithRelations(int|string $id, array $relations = [], array $columns = ['*']): ?Model;
}
