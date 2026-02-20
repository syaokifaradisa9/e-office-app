<?php

namespace Modules\Ticketing\Services;

use Modules\Ticketing\DataTransferObjects\AssetItemDTO;
use Modules\Ticketing\Enums\MaintenanceStatus;
use Modules\Ticketing\Models\AssetItem;
use Modules\Ticketing\Repositories\AssetItem\AssetItemRepository;
use Modules\Ticketing\Repositories\Maintenance\MaintenanceRepository;
use Illuminate\Support\Carbon;

class AssetItemService
{
    public function __construct(
        private AssetItemRepository $assetItemRepository,
        private MaintenanceRepository $maintenanceRepository
    ) {}

    public function store(AssetItemDTO $dto): AssetItem
    {
        $assetItem = $this->assetItemRepository->store($dto);
        $this->generateMaintenances($assetItem);
        return $assetItem;
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

    public function regenerateByModel(int $modelId): void
    {
        $assets = AssetItem::where('asset_model_id', $modelId)->get();
        foreach ($assets as $asset) {
            $this->generateMaintenances($asset);
        }
    }

    public function generateMaintenances(AssetItem $assetItem): void
    {
        $assetModel = $assetItem->assetModel;
        if (!$assetModel || $assetModel->maintenance_count <= 0) {
            return;
        }

        // 1. Delete ONLY Pending maintenances
        $this->maintenanceRepository->deletePendingByAssetItemId($assetItem->id);

        // 2. Determine Year and Periods logic
        // We use maintenance_count to split 12 months.
        $count = (int) $assetModel->maintenance_count;
        $periodLength = 12 / $count;

        // 3. Get all existing non-pending maintenances to find filled periods
        $existingRecords = $assetItem->maintenances()
            ->where('status', '!=', MaintenanceStatus::PENDING->value)
            ->orderBy('estimation_date', 'asc')
            ->get();

        // 4. Reference Point: the absolute latest maintenance that happened
        $latestRecord = $existingRecords->last();
        $refDateStr = $latestRecord?->estimation_date?->toDateString() 
            ?? $assetItem->last_maintenance_date 
            ?? $assetItem->created_at->toDateString();
        
        $refDate = Carbon::parse($refDateStr);
        $workYear = $refDate->year;

        // 5. Determine Month Offset within a period based on refDate
        // Example: if periodLength is 4 (3x/year) and refDate is Feb (Month 2).
        // Feb is Month 2 of Period [1,2,3,4]. Offset = (2-1) % 4 = 1.
        $refMonth = $refDate->month;
        $monthOffset = ($refMonth - 1) % $periodLength;

        // 6. Generate Potential Slots for the cycle
        $slots = [];
        // Cycle through periods
        for ($i = 0; $i < $count; $i++) {
            // Target month = (Start of period) + offset
            // Period i starts at month: 1 + (i * periodLength)
            $targetMonth = 1 + ($i * $periodLength) + $monthOffset;
            $slotDate = Carbon::create($workYear, (int)$targetMonth, 1)->startOfDay();
            
            // If the slot is in the past compared to the refDate, it might be the current period.
            // We only care about slots that are in the FUTURE of the reference date.
            if ($slotDate->gt($refDate->copy()->startOfDay())) {
                $slots[] = $slotDate;
            }
        }

        // 7. If this year is fully covered or no slots found, look into next year
        if (empty($slots)) {
            $nextYear = $workYear + 1;
            for ($i = 0; $i < $count; $i++) {
                $targetMonth = 1 + ($i * $periodLength) + $monthOffset;
                $slots[] = Carbon::create($nextYear, (int)$targetMonth, 1)->startOfDay();
            }
        }

        // 8. Filter out slots that fall into periods that ALREADY have a non-pending record
        foreach ($slots as $slot) {
            $slotMonth = $slot->month;
            $slotPeriodIdx = floor(($slotMonth - 1) / $periodLength);
            
            // Check if ANY existing record falls into this same period in this year
            $periodExists = $existingRecords->contains(function($rec) use ($slot, $periodLength, $slotPeriodIdx) {
                if ($rec->estimation_date->year != $slot->year) return false;
                $m = $rec->estimation_date->month;
                return floor(($m - 1) / $periodLength) == $slotPeriodIdx;
            });

            if (!$periodExists) {
                $this->maintenanceRepository->store([
                    'asset_item_id' => $assetItem->id,
                    'estimation_date' => $slot->toDateString(),
                    'status' => MaintenanceStatus::PENDING->value,
                ]);
            }
        }
    }
}
