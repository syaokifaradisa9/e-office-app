<?php

namespace Modules\Ticketing\Repositories\AssetCategory;

use Modules\Ticketing\Models\AssetCategory;
use Illuminate\Database\Eloquent\Collection;

interface AssetCategoryRepository
{
    public function getAll(array $filters = []): Collection;
    public function findById(int $id): ?AssetCategory;
    public function store(array $data): AssetCategory;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
}
