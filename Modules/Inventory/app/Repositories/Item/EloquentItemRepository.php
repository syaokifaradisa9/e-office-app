<?php

namespace Modules\Inventory\Repositories\Item;

use Illuminate\Database\Eloquent\Collection;
use Modules\Inventory\Models\Item;

class EloquentItemRepository implements ItemRepository
{
    public function findById(int $id): ?Item
    {
        return Item::find($id);
    }

    public function getBaseUnits(): Collection
    {
        return Item::whereNull('division_id')
            ->whereNull('reference_item_id')
            ->get(['id', 'name', 'unit_of_measure']);
    }

    public function getMostStocked(int $limit = 5, ?int $divisionId = null): Collection
    {
        $query = Item::query();

        if ($divisionId) {
            $query->where('division_id', $divisionId);
        } else {
            $query->whereNull('division_id');
        }

        return $query->orderByDesc('stock')
            ->limit($limit)
            ->get(['id', 'name', 'stock', 'unit_of_measure']);
    }

    public function getLeastStocked(int $limit = 5, ?int $divisionId = null): Collection
    {
        $query = Item::query();

        if ($divisionId) {
            $query->where('division_id', $divisionId);
        } else {
            $query->whereNull('division_id');
        }

        return $query->where('stock', '>', 0)
            ->orderBy('stock')
            ->limit($limit)
            ->get(['id', 'name', 'stock', 'unit_of_measure']);
    }

    public function getConversionTargets(Item $item): Collection
    {
        return Item::where('reference_item_id', $item->id)
            ->orWhere(function ($query) use ($item) {
                $query->where('category_id', $item->category_id)
                    ->where('id', '!=', $item->id)
                    ->where('division_id', $item->division_id);
            })
            ->get(['id', 'name', 'unit_of_measure', 'stock', 'multiplier']);
    }

    public function create(array $data): Item
    {
        return Item::create($data);
    }

    public function update(Item $item, array $data): Item
    {
        $item->update($data);
        return $item->refresh();
    }

    public function delete(Item $item): bool
    {
        return $item->delete();
    }

    public function getWarehouseItemsWithStock(): Collection
    {
        return Item::whereNull('division_id')
            ->where('stock', '>', 0)
            ->get(['id', 'name', 'stock', 'unit_of_measure', 'category_id', 'description']);
    }
}
