<?php

namespace Modules\Ticketing\Repositories\AssetItem;

use Modules\Ticketing\DataTransferObjects\AssetItemDTO;
use Modules\Ticketing\Models\AssetItem;

interface AssetItemRepository
{
    public function store(AssetItemDTO $dto): AssetItem;
    public function update(int $id, AssetItemDTO $dto): bool;
    public function delete(int $id): bool;
    public function findById(int $id): ?AssetItem;
}
