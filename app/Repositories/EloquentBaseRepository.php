<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class EloquentBaseRepository implements BaseRepository
{
    protected Model $model;

    /**
     * EloquentBaseRepository constructor
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Get all records
     */
    public function all(array $columns = ['*']): Collection
    {
        return $this->model->all($columns);
    }

    /**
     * Find a record by ID
     */
    public function find(int|string $id, array $columns = ['*']): ?Model
    {
        return $this->model->find($id, $columns);
    }

    /**
     * Create a new record
     */
    public function create(array $attributes): Model
    {
        return $this->model->create($attributes);
    }

    /**
     * Update a record
     */
    public function update(Model $model, array $attributes): Model
    {
        $model->fill($attributes);
        $model->save();

        return $model;
    }

    /**
     * Delete a record
     */
    public function delete(Model $model): bool
    {
        return (bool) $model->delete();
    }

    /**
     * Find a record by ID or fail
     *
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int|string $id, array $columns = ['*']): Model
    {
        return $this->model->findOrFail($id, $columns);
    }

    /**
     * Find records by criteria
     */
    public function findBy(array $criteria, array $columns = ['*']): Collection
    {
        return $this->model->where($criteria)->get($columns);
    }

    /**
     * Get paginated records
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $columns = ['*'])
    {
        return $this->model->paginate($perPage, $columns);
    }

    /**
     * Get the model instance
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    /**
     * Set the model instance
     *
     * @return $this
     */
    public function setModel(Model $model): self
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Find a record by ID with relationships
     */
    public function findWithRelations(int|string $id, array $relations = [], array $columns = ['*']): ?Model
    {
        return $this->model->with($relations)->find($id, $columns);
    }
}
