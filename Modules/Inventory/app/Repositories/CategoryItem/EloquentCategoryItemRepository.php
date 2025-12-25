<?php

namespace Modules\Inventory\Repositories\CategoryItem;

use Illuminate\Database\Eloquent\Collection;
use Modules\Inventory\Models\CategoryItem;

class EloquentCategoryItemRepository implements CategoryItemRepository
{
    public function getActiveCategories(): Collection
    {
        return CategoryItem::where('is_active', true)->orderBy('name')->get();
    }

    public function create(array $data): CategoryItem
    {
        return CategoryItem::create($data);
    }

    public function update(CategoryItem $categoryItem, array $data): CategoryItem
    {
        $categoryItem->update($data);

        return $categoryItem->refresh();
    }

    public function delete(CategoryItem $categoryItem): bool
    {
        return $categoryItem->delete();
    }

    public function hasItems(CategoryItem $categoryItem): bool
    {
        return $categoryItem->items()->exists();
    }
}
