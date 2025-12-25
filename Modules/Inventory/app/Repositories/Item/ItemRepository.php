<?php

namespace Modules\Inventory\Repositories\Item;

use Illuminate\Database\Eloquent\Collection;
use Modules\Inventory\Models\Item;

interface ItemRepository
{
    public function findById(int $id): ?Item;

    public function getBaseUnits(): Collection;

    public function getMostStocked(int $limit = 5, ?int $divisionId = null): Collection;

    public function getLeastStocked(int $limit = 5, ?int $divisionId = null): Collection;

    public function getConversionTargets(Item $item): Collection;

    public function create(array $data): Item;

    public function update(Item $item, array $data): Item;

    public function delete(Item $item): bool;

    public function getWarehouseItemsWithStock(): Collection;
}
