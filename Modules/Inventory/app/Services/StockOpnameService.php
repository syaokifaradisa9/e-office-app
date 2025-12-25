<?php

namespace Modules\Inventory\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Enums\InventoryPermission;
use Modules\Inventory\Enums\ItemTransactionType;
use Modules\Inventory\Models\Item;
use Modules\Inventory\Models\ItemTransaction;
use Modules\Inventory\Models\StockOpname;

class StockOpnameService
{
    public function getItemsForOpname(User $user, string $type = 'warehouse')
    {
        if ($type === 'warehouse') {
            return Item::whereNull('division_id')->get(['id', 'name', 'stock', 'unit_of_measure']);
        } else {
            if (! $user->division_id) {
                return collect([]);
            }

            return Item::where('division_id', $user->division_id)->get(['id', 'name', 'stock', 'unit_of_measure']);
        }
    }

    public function createWarehouse(array $data, User $user): StockOpname
    {
        return DB::transaction(function () use ($data, $user) {
            $opname = StockOpname::create([
                'user_id' => $user->id,
                'division_id' => null,
                'opname_date' => $data['opname_date'],
                'notes' => $data['notes'] ?? null,
                'status' => 'Draft',
            ]);

            foreach ($data['items'] as $itemData) {
                $item = Item::find($itemData['item_id']);
                $opname->items()->create([
                    'item_id' => $itemData['item_id'],
                    'system_stock' => $item->stock,
                    'physical_stock' => $itemData['physical_stock'],
                    'difference' => $itemData['physical_stock'] - $item->stock,
                    'notes' => $itemData['notes'] ?? null,
                ]);
            }

            return $opname;
        });
    }

    public function createDivision(array $data, User $user): StockOpname
    {
        return DB::transaction(function () use ($data, $user) {
            $opname = StockOpname::create([
                'user_id' => $user->id,
                'division_id' => $user->division_id,
                'opname_date' => $data['opname_date'],
                'notes' => $data['notes'] ?? null,
                'status' => 'Draft',
            ]);

            foreach ($data['items'] as $itemData) {
                $item = Item::find($itemData['item_id']);
                $opname->items()->create([
                    'item_id' => $itemData['item_id'],
                    'system_stock' => $item->stock,
                    'physical_stock' => $itemData['physical_stock'],
                    'difference' => $itemData['physical_stock'] - $item->stock,
                    'notes' => $itemData['notes'] ?? null,
                ]);
            }

            return $opname;
        });
    }

    public function update(StockOpname $opname, array $data, User $user): StockOpname
    {
        if (! $this->canManage($opname, $user)) {
            throw new \Exception('Unauthorized to update this stock opname');
        }

        return DB::transaction(function () use ($opname, $data) {
            $opname->update([
                'opname_date' => $data['opname_date'],
                'notes' => $data['notes'] ?? null,
            ]);

            $opname->items()->delete();
            foreach ($data['items'] as $itemData) {
                $item = Item::find($itemData['item_id']);
                $opname->items()->create([
                    'item_id' => $itemData['item_id'],
                    'system_stock' => $item->stock,
                    'physical_stock' => $itemData['physical_stock'],
                    'difference' => $itemData['physical_stock'] - $item->stock,
                    'notes' => $itemData['notes'] ?? null,
                ]);
            }

            return $opname->fresh(['items.item']);
        });
    }

    public function delete(StockOpname $opname, User $user): bool
    {
        if (! $this->canManage($opname, $user)) {
            throw new \Exception('Unauthorized to delete this stock opname');
        }

        return DB::transaction(function () use ($opname) {
            $opname->items()->delete();

            return $opname->delete();
        });
    }

    public function confirm(StockOpname $opname, User $user): StockOpname
    {
        if ($opname->status !== 'Draft') {
            throw new \Exception('Stock opname sudah dikonfirmasi');
        }

        return DB::transaction(function () use ($opname, $user) {
            foreach ($opname->items as $opnameItem) {
                $item = Item::find($opnameItem->item_id);
                $difference = $opnameItem->difference;

                if ($item && $difference != 0) {
                    // Create transaction record
                    ItemTransaction::create([
                        'date' => now(),
                        'type' => $difference > 0 ? ItemTransactionType::StockOpnameMore : ItemTransactionType::StockOpnameLess,
                        'item_id' => $item->id,
                        'quantity' => abs($difference),
                        'user_id' => $user->id,
                        'description' => 'Stock opname adjustment',
                    ]);

                    // Update item stock
                    $item->update(['stock' => $opnameItem->physical_stock]);
                }
            }

            $opname->update(['status' => 'Confirmed']);

            return $opname->fresh(['items.item']);
        });
    }

    public function canManage(StockOpname $opname, User $user): bool
    {
        // Check if status is Draft
        if ($opname->status !== 'Draft') {
            return false;
        }

        // Check if user is the creator
        if ($opname->user_id !== $user->id) {
            return false;
        }

        // Warehouse stock opname
        if ($opname->division_id === null) {
            return $user->can(InventoryPermission::ManageWarehouseStockOpname->value);
        }

        // Division stock opname
        return $user->can(InventoryPermission::ManageDivisionStockOpname->value)
            && $opname->division_id === $user->division_id;
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
