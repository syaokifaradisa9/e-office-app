<?php

namespace Modules\Ticketing\Repositories\Maintenance;

use Modules\Ticketing\Models\Maintenance;
use Modules\Ticketing\Enums\MaintenanceStatus;
use Illuminate\Support\Carbon;

class EloquentMaintenanceRepository implements MaintenanceRepository
{
    public function store(array $data): Maintenance
    {
        return Maintenance::create($data);
    }

    public function update(int $id, array $data): bool
    {
        return Maintenance::where('id', $id)->update($data);
    }

    public function findById(int $id): ?Maintenance
    {
        return Maintenance::with(['assetItem.assetModel.checklists', 'checklists'])->find($id);
    }

    public function deletePendingByAssetItemId(int $assetItemId): void
    {
        Maintenance::where('asset_item_id', $assetItemId)
            ->where('status', MaintenanceStatus::PENDING->value)
            ->delete();
    }

    public function getLatestNonPendingMaintenanceDate(int $assetItemId): ?Carbon
    {
        $maintenance = Maintenance::where('asset_item_id', $assetItemId)
            ->where('status', '!=', MaintenanceStatus::PENDING->value)
            ->latest('estimation_date')
            ->first();

        if (!$maintenance) {
            return null;
        }

        // Return the actual_date if present, otherwise estimation_date
        $latest = $maintenance->actual_date ?: $maintenance->estimation_date;
        return $latest ? Carbon::parse($latest) : null;
    }
}
