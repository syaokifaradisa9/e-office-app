<?php

namespace Modules\Inventory\Repositories\StockOpname;

use Modules\Inventory\Models\StockOpname;

interface StockOpnameRepository
{
    public function hasOpnameThisMonth(?int $divisionId = null): bool;

    public function hasActiveOpname(?int $divisionId = null): bool;

    public function create(array $data): StockOpname;

    public function findById(int $id): ?StockOpname;
}
