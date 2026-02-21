<?php

namespace Modules\Ticketing\Services;

use Modules\Ticketing\Models\Maintenance;
use Modules\Ticketing\Repositories\Maintenance\MaintenanceRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Modules\Ticketing\Enums\AssetItemStatus;


class MaintenanceService
{
    public function __construct(
        private MaintenanceRepository $maintenanceRepository,
        private \Modules\Ticketing\Repositories\AssetItemRefinement\AssetItemRefinementRepository $refinementRepository
    ) {}

    public function findById(int $id): ?Maintenance
    {
        return $this->maintenanceRepository->findById($id);
    }

    public function isActionable(Maintenance $maintenance): bool
    {
        return !Maintenance::where('asset_item_id', $maintenance->asset_item_id)
            ->where('estimation_date', '<', $maintenance->estimation_date)
            ->where('status', '!=', \Modules\Ticketing\Enums\MaintenanceStatus::CONFIRMED->value)
            ->exists();
    }

    public function markAsCompleted(int $id, ?string $note, ?string $actualDate = null): Maintenance
    {
        $maintenance = Maintenance::findOrFail($id);
        $maintenance->update([
            'status' => \Modules\Ticketing\Enums\MaintenanceStatus::FINISH,
            'note' => $note,
            'actual_date' => $actualDate ?: now()->toDateString(),
            'user_id' => auth()->id(),
        ]);

        return $maintenance;
    }

    public function saveChecklist(int $id, \Modules\Ticketing\DataTransferObjects\MaintenanceChecklistDTO $dto): Maintenance
    {
        $maintenance = $this->maintenanceRepository->findById($id);
        if (!$maintenance) {
            throw new \Exception("Maintenance record not found.");
        }

        if ($maintenance->status === \Modules\Ticketing\Enums\MaintenanceStatus::CONFIRMED) {
            throw new \Exception("Maintenance record has been confirmed and cannot be updated.");
        }

        // Determine status based on user's checkbox decision
        $status = $dto->needs_further_repair
            ? \Modules\Ticketing\Enums\MaintenanceStatus::REFINEMENT
            : \Modules\Ticketing\Enums\MaintenanceStatus::FINISH;

        // Save checklists to new table
        $maintenance->checklists()->delete();
        foreach ($dto->checklists as $item) {
            $maintenance->checklists()->create([
                'checklist_id' => $item['checklist_id'],
                'label' => $item['label'],
                'description' => $item['description'] ?? null,
                'value' => $item['value'] === 'Baik' ? 'Good' : 'Bad',
                'note' => $item['note'] ?? null,
                'followup' => $item['follow_up'] ?? null,
            ]);
        }

        $attachments = $maintenance->attachments ?? [];
        if (!empty($dto->attachments)) {
            foreach ($dto->attachments as $file) {
                $path = $file->store('maintenance-evidence', 'public');
                $attachments[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'url' => Storage::url($path),
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ];
            }
        }

        $maintenance->update([
            'actual_date' => $dto->actual_date,
            'note' => $dto->note,
            'checklist_results' => $dto->checklists,
            'status' => $status,
            'user_id' => auth()->id(),
            'attachments' => $attachments,
        ]);

        // Update asset_item status based on needs_further_repair
        $maintenance->assetItem()->update([
            'status' => $dto->needs_further_repair ? AssetItemStatus::Refinement->value : AssetItemStatus::Available->value,
        ]);


        return $maintenance;
    }

    public function confirm(int $id): Maintenance
    {
        $maintenance = Maintenance::findOrFail($id);
        
        if ($maintenance->status !== \Modules\Ticketing\Enums\MaintenanceStatus::FINISH) {
            throw new \Exception("Hanya maintenance dengan status Selesai yang dapat dikonfirmasi.");
        }

        $maintenance->update([
            'status' => \Modules\Ticketing\Enums\MaintenanceStatus::CONFIRMED,
        ]);

        return $maintenance;
    }

    public function cancel(int $id, ?string $note): Maintenance
    {
        $maintenance = Maintenance::findOrFail($id);
        $maintenance->update([
            'status' => \Modules\Ticketing\Enums\MaintenanceStatus::CANCELLED,
            'note' => $note,
            'user_id' => auth()->id(),
        ]);

        return $maintenance;
    }

    public function saveRefinement(int $id, array $data): \Modules\Ticketing\Models\AssetItemRefinement
    {
        $maintenance = $this->maintenanceRepository->findById($id);
        if (!$maintenance) {
            throw new \Exception("Maintenance record not found.");
        }

        $attachments = [];
        if (!empty($data['attachments'])) {
            foreach ($data['attachments'] as $file) {
                $path = $file->store('refinement-evidence', 'public');
                $attachments[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'url' => Storage::url($path),
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ];
            }
        }

        return $this->refinementRepository->store([
            'maintenance_id' => $id,
            'date' => $data['date'],
            'description' => $data['description'],
            'note' => $data['note'],
            'result' => $data['result'],
            'attachments' => $attachments,
        ]);
    }

    public function getRefinements(int $id)
    {
        return $this->refinementRepository->getByMaintenanceId($id);
    }
}
