<?php

namespace App\Services;

use App\DataTransferObjects\PositionDTO;
use App\Models\Position;
use App\Repositories\Position\PositionRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class PositionService
{
    private const CACHE_KEY = 'positions_all';

    public function __construct(private PositionRepository $positionRepository) {}

    public function getAll(): Collection
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return $this->positionRepository->all();
        });
    }

    public function getActive(): Collection
    {
        return $this->positionRepository->findBy(['is_active' => true]);
    }

    public function store(PositionDTO $dto): Position
    {
        $position = $this->positionRepository->create($dto->toModelPayload());
        $this->clearCache();

        return $position;
    }

    public function update(Position $position, PositionDTO $dto): Position
    {
        $updated = $this->positionRepository->update($position, $dto->toModelPayload());
        $this->clearCache();

        return $updated;
    }

    public function delete(Position $position): bool
    {
        $result = $this->positionRepository->delete($position);
        $this->clearCache();

        return $result;
    }

    private function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
