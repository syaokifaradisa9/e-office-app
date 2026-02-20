<?php

namespace Modules\Ticketing\Services;

use Modules\Ticketing\Models\AssetModel;
use Modules\Ticketing\DataTransferObjects\AssetModelDTO;
use Modules\Ticketing\Repositories\AssetModel\AssetModelRepository;
use Illuminate\Database\Eloquent\Collection;

class AssetModelService
{
    public function __construct(
        private AssetModelRepository $assetModelRepository,
        private AssetItemService $assetItemService
    ) {}

    public function getAll(array $filters = []): Collection
    {
        return $this->assetModelRepository->getAll($filters);
    }

    public function store(AssetModelDTO $dto): AssetModel
    {
        return $this->assetModelRepository->store($dto->toArray());
    }

    public function update(int $id, AssetModelDTO $dto): bool
    {
        $oldModel = AssetModel::find($id);
        $oldMaintenanceCount = $oldModel?->maintenance_count;
        
        $updated = $this->assetModelRepository->update($id, $dto->toArray());
        
        if ($updated && $oldMaintenanceCount !== $dto->maintenance_count) {
            $this->assetItemService->regenerateByModel($id);
        }
        
        return $updated;
    }

    public function delete(int $id): bool
    {
        return $this->assetModelRepository->delete($id);
    }
}
