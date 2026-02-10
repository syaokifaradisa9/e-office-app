<?php

namespace Modules\Inventory\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\DataTransferObjects\StockOpnameDTO;
use Modules\Inventory\Enums\InventoryPermission;
use Modules\Inventory\Enums\ItemTransactionType;
use Modules\Inventory\Models\Item;
use Modules\Inventory\Models\ItemTransaction;
use Modules\Inventory\Models\StockOpname;
use Modules\Inventory\Repositories\StockOpname\StockOpnameRepository;

class StockOpnameService
{
    public function __construct(
        private StockOpnameRepository $repository
    ) {}

    public function getItemsForOpname(User $user, ?int $divisionId = null)
    {
        if ($divisionId === null) {
            return Item::whereNull('division_id')->get(['id', 'name', 'stock', 'unit_of_measure']);
        } else {
            return Item::where('division_id', $divisionId)->get(['id', 'name', 'stock', 'unit_of_measure']);
        }
    }

    public function initializeOpname(StockOpnameDTO $dto, User $user): StockOpname
    {
        if ($this->repository->hasActiveOpname($dto->division_id)) {
            throw new \Exception('Masih ada Stock Opname yang belum selesai (Pending/Proses).');
        }

        return $this->repository->create([
            'user_id' => $user->id,
            'division_id' => $dto->division_id,
            'opname_date' => $dto->opname_date,
            'notes' => $dto->notes,
            'status' => 'Pending',
        ]);
    }

    public function savePhysicalStock(StockOpname $opname, StockOpnameDTO $dto, User $user): StockOpname
    {
        if (!in_array($opname->status, ['Pending', 'Proses'])) {
            throw new \Exception('Status Stock Opname tidak valid untuk proses ini.');
        }

        return DB::transaction(function () use ($opname, $dto, $user) {
            $opname->items()->delete();

            foreach ($dto->items as $itemData) {
                // Determine system stock based on current status or original snapshot?
                // Request says: "hanya mengisikan stok fisik tanpa adanya data stok sistem" in step 3.
                // But in step 5: "akan muncul stok sistem sebelum stok fisik".
                // I'll take the current item stock as system stock when saving.
                $item = Item::find($itemData['item_id']);
                
                $opname->items()->create([
                    'item_id' => $itemData['item_id'],
                    'system_stock' => $item->stock,
                    'physical_stock' => $itemData['physical_stock'],
                    'difference' => $itemData['physical_stock'] - $item->stock,
                    'notes' => $itemData['notes'] ?? null,
                ]);
            }

            if ($dto->status === 'Confirmed') {
                $this->confirmStockUpdate($opname, $user);
            } else {
                $opname->update(['status' => 'Proses']);
            }

            return $opname->fresh(['items.item']);
        });
    }

    private function confirmStockUpdate(StockOpname $opname, User $user)
    {
        foreach ($opname->items as $opnameItem) {
            $item = Item::find($opnameItem->item_id);
            $difference = $opnameItem->difference;

            if ($item && $difference != 0) {
                ItemTransaction::create([
                    'date' => now(),
                    'type' => $difference > 0 ? ItemTransactionType::StockOpnameMore : ItemTransactionType::StockOpnameLess,
                    'item_id' => $item->id,
                    'quantity' => abs($difference),
                    'user_id' => $user->id,
                    'description' => 'Stock opname adjustment (Confirmed)',
                ]);

                $item->update(['stock' => $opnameItem->physical_stock]);
            }
        }

        $opname->update([
            'status' => 'Confirmed',
            'confirmed_at' => now(),
        ]);
    }

    public function finalizeStock(StockOpname $opname, StockOpnameDTO $dto, User $user): StockOpname
    {
        $this->validateFinalizationRule($opname);

        return DB::transaction(function () use ($opname, $dto, $user) {
            foreach ($dto->items as $itemData) {
                $opnameItem = $opname->items()->where('item_id', $itemData['item_id'])->first();
                if ($opnameItem) {
                    $item = Item::find($opnameItem->item_id);
                    
                    // Logic: If there's a difference between final_stock and previous physical_stock
                    $additionalDifference = $itemData['final_stock'] - $opnameItem->physical_stock;

                    if ($additionalDifference != 0) {
                        ItemTransaction::create([
                            'date' => now(),
                            'type' => $additionalDifference > 0 ? ItemTransactionType::StockOpnameMore : ItemTransactionType::StockOpnameLess,
                            'item_id' => $item->id,
                            'quantity' => abs($additionalDifference),
                            'user_id' => $user->id,
                            'description' => 'Final stock adjustment (Selesai)',
                        ]);

                        $item->update(['stock' => $itemData['final_stock']]);
                    }

                    $opnameItem->update([
                        'final_stock' => $itemData['final_stock'],
                        'final_notes' => $itemData['final_notes'] ?? null,
                    ]);
                }
            }

            $opname->update(['status' => 'Selesai']);

            return $opname->fresh(['items.item']);
        });
    }

    private function validateFinalizationRule(StockOpname $opname)
    {
        if ($opname->status !== 'Confirmed') {
            throw new \Exception('Hanya Stock Opname berstatus Confirmed yang dapat difinalisasi.');
        }

        $confirmedAt = Carbon::parse($opname->confirmed_at);
        $now = now();

        if ($confirmedAt->isSameDay($now)) {
            throw new \Exception('Finalisasi tidak boleh dilakukan di hari yang sama dengan konfirmasi.');
        }

        if ($now->diffInDays($confirmedAt) > 5) {
            // After 5 days, status should be auto-set to Selesai if not already done.
            // But if they try to manually finalize after 5 days, we might allow it or just block it if it's already "expired".
            // Requested: "jika telah lewat 5 hari maka status menjadi Selesai dan stok penyesuaian menjadi sama seperti stock opname"
            throw new \Exception('Batas waktu finalisasi (5 hari) telah berakhir.');
        }
    }

    public function isMenuHidden(?int $divisionId = null): bool
    {
        return $this->repository->hasActiveOpname($divisionId);
    }

    public function canManage(StockOpname $opname, User $user): bool
    {
        if (in_array($opname->status, ['Confirmed', 'Selesai'])) {
            return false;
        }

        // Creator check
        if ($opname->user_id !== $user->id) {
            return false;
        }

        if ($opname->division_id === null) {
            return $user->can(InventoryPermission::ManageWarehouseStockOpname->value);
        }

        return $user->can(InventoryPermission::ManageDivisionStockOpname->value)
            && $opname->division_id === $user->division_id;
    }

    public function canProcess(StockOpname $opname, User $user): bool
    {
        if (!in_array($opname->status, ['Pending', 'Proses'])) {
            return false;
        }

        if ($opname->division_id === null) {
            // Gudang utama manager or similar
            return $user->can(InventoryPermission::ProcessStockOpname->value);
        }

        return $user->can(InventoryPermission::ProcessStockOpname->value)
            && $opname->division_id === $user->division_id;
    }

    public function canFinalize(StockOpname $opname, User $user): bool
    {
        if ($opname->status !== 'Confirmed') {
            return false;
        }

        return $user->can(InventoryPermission::FinalizeStockOpname->value);
    }

    public function canView(StockOpname $opname, User $user): bool
    {
        if ($user->can(InventoryPermission::ViewAllStockOpname->value)) {
            return true;
        }

        if ($opname->division_id === null) {
            return $user->can(InventoryPermission::ViewWarehouseStockOpname->value);
        }

        return $user->can(InventoryPermission::ViewDivisionStockOpname->value)
            && $opname->division_id === $user->division_id;
    }

    public function getType(StockOpname $opname): string
    {
        return $opname->division_id === null ? 'warehouse' : 'division';
    }
}
