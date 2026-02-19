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

    public function getAllByAssetModelId(int $assetModelId): Collection
    {
        return $this->checklistRepository->getAllByAssetModelId($assetModelId);
    }

    public function store(int $assetModelId, ChecklistDTO $dto): Checklist
    {
        return $this->checklistRepository->store([
            'asset_model_id' => $assetModelId,
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
