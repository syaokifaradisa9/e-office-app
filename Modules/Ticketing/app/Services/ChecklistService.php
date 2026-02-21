<?php

namespace Modules\Ticketing\Services;

use Modules\Ticketing\Models\Checklist;
use Modules\Ticketing\DataTransferObjects\ChecklistDTO;
use Modules\Ticketing\Repositories\Checklist\ChecklistRepository;
use Illuminate\Database\Eloquent\Collection;

class ChecklistService
{
    public function __construct(
        private ChecklistRepository $checklistRepository
    ) {}

    public function getAllByAssetCategoryId(int $assetCategoryId): Collection
    {
        return $this->checklistRepository->getAllByAssetCategoryId($assetCategoryId);
    }

    public function store(int $assetCategoryId, ChecklistDTO $dto): Checklist
    {
        return $this->checklistRepository->store([
            'asset_category_id' => $assetCategoryId,
            ...$dto->toArray(),
        ]);
    }

    public function update(int $id, ChecklistDTO $dto): bool
    {
        return $this->checklistRepository->update($id, $dto->toArray());
    }

    public function delete(int $id): bool
    {
        return $this->checklistRepository->delete($id);
    }
}
