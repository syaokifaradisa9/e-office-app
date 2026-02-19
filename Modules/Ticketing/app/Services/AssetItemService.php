<?php

namespace Modules\Ticketing\Services;

use Modules\Ticketing\DataTransferObjects\AssetItemDTO;
use Modules\Ticketing\Models\AssetItem;
use Modules\Ticketing\Repositories\AssetItem\AssetItemRepository;

class AssetItemService
{
    public function __construct(
        private AssetItemRepository $assetItemRepository
    ) {}

    public function store(AssetItemDTO $dto): AssetItem
    {
        return $this->assetItemRepository->store($dto);
    }

    public function update(int $id, AssetItemDTO $dto): bool
    {
        return $this->assetItemRepository->update($id, $dto);
    }

    public function delete(int $id): bool
    {
        return $this->assetItemRepository->delete($id);
    }

    public function findById(int $id): ?AssetItem
    {
        return $this->assetItemRepository->findById($id);
    }
}
