<?php

namespace Modules\Ticketing\Services;

use Modules\Ticketing\Models\AssetCategory;
use Modules\Ticketing\DataTransferObjects\AssetCategoryDTO;
use Modules\Ticketing\Repositories\AssetCategory\AssetCategoryRepository;
use Illuminate\Database\Eloquent\Collection;

class AssetCategoryService
{
    public function __construct(
        private AssetCategoryRepository $assetCategoryRepository,
        private AssetItemService $assetItemService
    ) {}

    public function getAll(array $filters = []): Collection
    {
        return $this->assetCategoryRepository->getAll($filters);
    }

    public function store(AssetCategoryDTO $dto): AssetCategory
    {
        return $this->assetCategoryRepository->store($dto->toArray());
    }

    public function update(int $id, AssetCategoryDTO $dto): bool
    {
        $oldCategory = AssetCategory::find($id);
        $oldMaintenanceCount = $oldCategory?->maintenance_count;
        
        $updated = $this->assetCategoryRepository->update($id, $dto->toArray());
        
        if ($updated && $oldMaintenanceCount !== $dto->maintenance_count) {
            $this->assetItemService->regenerateByCategory($id);
        }
        
        return $updated;
    }

    public function delete(int $id): bool
    {
        return $this->assetCategoryRepository->delete($id);
    }
}
