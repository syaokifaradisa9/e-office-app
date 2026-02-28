<?php

namespace Modules\Ticketing\Repositories\AssetItemRefinement;

use Modules\Ticketing\Models\AssetItemRefinement;

interface AssetItemRefinementRepository
{
    public function store(array $data): AssetItemRefinement;
    public function getByMaintenanceId(int $maintenanceId);
    public function delete(int $id): bool;
    public function findById(int $id): ?AssetItemRefinement;
    public function update(int $id, array $data): bool;
}
