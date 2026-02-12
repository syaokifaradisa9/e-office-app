<?php

namespace Modules\Inventory\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\DataTransferObjects\StockOpnameDTO;
use Modules\Inventory\Enums\InventoryPermission;
use Modules\Inventory\Enums\ItemTransactionType;
use Modules\Inventory\Enums\StockOpnameStatus;
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
            throw new \Exception('Masih ada Stock Opname yang belum selesai.');
        }

        return $this->repository->create([
            'user_id' => $user->id,
            'division_id' => $dto->division_id,
            'opname_date' => $dto->opname_date,
            'notes' => $dto->notes,
            'status' => StockOpnameStatus::Pending,
        ]);
    }

    /**
     * Save physical stock data from process form.
     * Draft = status "Process"
     * Confirm = status "Stock Opname" + adjust stock + record transactions
     */
    public function savePhysicalStock(StockOpname $opname, StockOpnameDTO $dto, User $user): StockOpname
    {
        if (!in_array($opname->status, [StockOpnameStatus::Pending, StockOpnameStatus::Proses])) {
            throw new \Exception('Status Stock Opname tidak valid untuk proses ini.');
        }

        return DB::transaction(function () use ($opname, $dto, $user) {
            $opname->items()->delete();

            foreach ($dto->items as $itemData) {
                $item = Item::find($itemData['item_id']);

                $physicalStock = $itemData['physical_stock'] ?? null;

                // Requirement: If confirming (not draft), treat empty input as 0
                if ($dto->status === StockOpnameStatus::StockOpname && is_null($physicalStock)) {
                    $physicalStock = 0;
                }

                $opname->items()->create([
                    'item_id' => $itemData['item_id'],
                    'system_stock' => $item->stock,
                    'physical_stock' => $physicalStock,
                    'notes' => $itemData['notes'] ?? null,
                ]);
            }

            if ($dto->status === StockOpnameStatus::StockOpname) {
                $this->confirmStockUpdate($opname, $user);
            } else {
                $opname->update(['status' => StockOpnameStatus::Proses]);
            }

            return $opname->fresh(['items.item']);
        });
    }

    /**
     * When user confirms the stock opname:
     * - Status changes to "Stock Opname"
     * - Stock adjustments are applied to items
     * - Changes recorded in item_transactions
     */
    private function confirmStockUpdate(StockOpname $opname, User $user)
    {
        foreach ($opname->items as $opnameItem) {
            $item = Item::find($opnameItem->item_id);
            // Formula: system_stock - physical_stock
            $difference = $opnameItem->system_stock - $opnameItem->physical_stock;

            if ($item && !is_null($opnameItem->physical_stock) && $difference != 0) {
                ItemTransaction::create([
                    'date' => now(),
                    // system > physical (diff > 0) -> Kurang (Less)
                    // system < physical (diff < 0) -> Lebih (More)
                    'type' => $difference > 0 ? ItemTransactionType::StockOpnameLess : ItemTransactionType::StockOpnameMore,
                    'item_id' => $item->id,
                    'quantity' => abs($difference),
                    'user_id' => $user->id,
                    'description' => 'Penyesuaian stock opname',
                ]);

                $item->update(['stock' => $opnameItem->physical_stock]);
            }
        }

        $opname->update([
            'status' => StockOpnameStatus::StockOpname,
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

                    // Formula: system_stock - final_stock (compared to current system stock which was the opname start stock)
                    // But wait, the previous adjustment already happened. 
                    // So we need the delta between current physical and final.
                    $additionalDifference = $opnameItem->physical_stock - $itemData['final_stock'];

                    if ($additionalDifference != 0) {
                        ItemTransaction::create([
                            'date' => now(),
                            // positive -> physical > final -> Less (Kurang)
                            // negative -> physical < final -> More (Lebih)
                            'type' => $additionalDifference > 0 ? ItemTransactionType::StockOpnameLess : ItemTransactionType::StockOpnameMore,
                            'item_id' => $item->id,
                            'quantity' => abs($additionalDifference),
                            'user_id' => $user->id,
                            'description' => 'Penyesuaian finalisasi stock opname',
                        ]);

                        $item->update(['stock' => $itemData['final_stock']]);
                    }

                    $opnameItem->update([
                        'final_stock' => $itemData['final_stock'],
                        'final_notes' => $itemData['final_notes'] ?? null,
                    ]);
                }
            }

            $opname->update(['status' => StockOpnameStatus::Finish]);

            return $opname->fresh(['items.item']);
        });
    }

    private function validateFinalizationRule(StockOpname $opname)
    {
        if ($opname->status !== StockOpnameStatus::StockOpname) {
            throw new \Exception('Hanya Stock Opname berstatus "Stock Opname" yang dapat difinalisasi.');
        }

        $opnameDate = Carbon::parse($opname->opname_date);
        $confirmedAt = Carbon::parse($opname->confirmed_at);
        $now = now();

        if ($opnameDate->isSameDay($now)) {
            throw new \Exception('Finalisasi tidak boleh dilakukan di hari yang sama dengan Tanggal Opname.');
        }

        if ($now->diffInDays($confirmedAt) > 5) {
            throw new \Exception('Batas waktu finalisasi (5 hari) telah berakhir.');
        }
    }

    /**
     * Check if menus (Kategori Barang Gudang, Monitoring Stok, Permintaan Barang)
     * should be hidden/blocked.
     *
     * Requirement 6-8:
     * - If user's division has active opname → block for that user
     * - If warehouse (division_id=null) has active opname → block for ALL users
     */
    public function isMenuHidden(?int $userDivisionId = null): bool
    {
        // Check if warehouse (division_id=null) has active opname → blocks everyone
        if ($this->repository->hasActiveOpname(null)) {
            return true;
        }

        // Check if user's division has active opname → blocks that division's user
        if ($userDivisionId && $this->repository->hasActiveOpname($userDivisionId)) {
            return true;
        }

        return false;
    }

    public function canManage(StockOpname $opname, User $user): bool
    {
        if (in_array($opname->status, [StockOpnameStatus::StockOpname, StockOpnameStatus::Finish])) {
            return false;
        }

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
        if (!in_array($opname->status, [StockOpnameStatus::Pending, StockOpnameStatus::Proses])) {
            return false;
        }

        if ($opname->division_id === null) {
            return $user->can(InventoryPermission::ProcessStockOpname->value);
        }

        return $user->can(InventoryPermission::ProcessStockOpname->value)
            && $opname->division_id === $user->division_id;
    }

    public function canFinalize(StockOpname $opname, User $user): bool
    {
        if ($opname->status !== StockOpnameStatus::StockOpname) {
            return false;
        }

        // Check Permission
        if (! $user->can(InventoryPermission::FinalizeStockOpname->value)) {
            return false;
        }

        // Check Division access
        if ($user->can(InventoryPermission::ViewAllStockOpname->value)) {
            return true;
        }

        if ($opname->division_id === null) {
            return true; // Warehouse opname can be finalized by anyone with the permission
        }

        return $opname->division_id === $user->division_id;
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
