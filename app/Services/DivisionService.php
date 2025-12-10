<?php

namespace App\Services;

use App\DataTransferObjects\DivisionDTO;
use App\Models\Division;
use App\Repositories\Division\DivisionRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class DivisionService
{
    private const CACHE_KEY = 'divisions_all';

    public function __construct(private DivisionRepository $divisionRepository) {}

    public function getAll(): Collection
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return $this->divisionRepository->all();
        });
    }

    public function getActive(): Collection
    {
        return $this->divisionRepository->findBy(['is_active' => true]);
    }

    public function store(DivisionDTO $dto): Division
    {
        $division = $this->divisionRepository->create($dto->toModelPayload());
        $this->clearCache();

        return $division;
    }

    public function update(Division $division, DivisionDTO $dto): Division
    {
        $updated = $this->divisionRepository->update($division, $dto->toModelPayload());
        $this->clearCache();

        return $updated;
    }

    public function delete(Division $division): bool
    {
        $result = $this->divisionRepository->delete($division);
        $this->clearCache();

        return $result;
    }

    private function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
