<?php

namespace Modules\Ticketing\Repositories\Maintenance;

use Modules\Ticketing\Models\Maintenance;
use Illuminate\Support\Carbon;

interface MaintenanceRepository
{
    public function store(array $data): Maintenance;
    public function update(int $id, array $data): bool;
    public function findById(int $id): ?Maintenance;
    public function deletePendingByAssetItemId(int $assetItemId): void;
    public function getLatestNonPendingMaintenanceDate(int $assetItemId): ?Carbon;
}
