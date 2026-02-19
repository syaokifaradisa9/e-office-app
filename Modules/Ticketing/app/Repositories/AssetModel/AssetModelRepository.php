<?php

namespace Modules\Ticketing\Repositories\AssetModel;

use Modules\Ticketing\Models\AssetModel;
use Illuminate\Database\Eloquent\Collection;

interface AssetModelRepository
{
    public function getAll(array $filters = []): Collection;
    public function findById(int $id): ?AssetModel;
    public function store(array $data): AssetModel;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
}
