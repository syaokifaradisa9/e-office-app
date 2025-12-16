<?php

namespace Modules\Inventory\Repositories\CategoryItem;

use Illuminate\Database\Eloquent\Collection;
use Modules\Inventory\Models\CategoryItem;

interface CategoryItemRepository
{
    public function getActiveCategories(): Collection;

    public function create(array $data): CategoryItem;

    public function update(CategoryItem $categoryItem, array $data): CategoryItem;

    public function delete(CategoryItem $categoryItem): bool;
}
