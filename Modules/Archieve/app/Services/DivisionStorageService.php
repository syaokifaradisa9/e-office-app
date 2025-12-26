<?php

namespace Modules\Archieve\Services;

use App\Models\Division;
use Modules\Archieve\DataTransferObjects\StoreDivisionStorageDTO;
use Modules\Archieve\Models\DivisionStorage;
use Modules\Archieve\Repositories\DivisionStorage\DivisionStorageRepository;

class DivisionStorageService
{
    public function __construct(
        private DivisionStorageRepository $repository
    ) {}

    public function all()
    {
        return $this->repository->all();
    }

    public function find(int $id)
    {
        return $this->repository->find($id);
    }

    public function store(StoreDivisionStorageDTO $dto)
    {
        return $this->repository->store($dto->toArray());
    }

    public function update(DivisionStorage $storage, StoreDivisionStorageDTO $dto)
    {
        return $this->repository->update($storage, $dto->toArray());
    }

    public function delete(DivisionStorage $storage)
    {
        return $this->repository->delete($storage);
    }

    /**
     * Get all divisions with their storage info.
     */
    public function getDivisionsWithStorage()
    {
        $divisions = Division::orderBy('name')->get();
        $storages = $this->repository->all()->keyBy('division_id');

        return $divisions->map(function ($division) use ($storages) {
            $storage = $storages->get($division->id);
            return [
                'id' => $division->id,
                'name' => $division->name,
                'storage_id' => $storage?->id,
                'max_size' => $storage?->max_size ?? 0,
                'max_size_gb' => $storage ? round($storage->max_size / (1024 * 1024 * 1024), 2) : 0,
                'max_size_label' => $storage?->max_size_label ?? '0 B',
                'used_size' => $storage?->used_size ?? 0,
                'used_size_label' => $storage?->used_size_label ?? '0 B',
                'usage_percentage' => $storage?->usage_percentage ?? 0,
            ];
        });
    }
}
