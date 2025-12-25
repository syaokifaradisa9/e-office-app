<?php

namespace Modules\Inventory\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Inventory\DataTransferObjects\StoreCategoryItemDTO;
use Modules\Inventory\DataTransferObjects\UpdateCategoryItemDTO;
use Modules\Inventory\Models\CategoryItem;
use Modules\Inventory\Repositories\CategoryItem\CategoryItemRepository;

class CategoryItemService
{
    private const CACHE_KEY = 'inventory_categories_active';

    public function __construct(private CategoryItemRepository $categoryItemRepository) {}

    public function getActiveCategories(): Collection
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return $this->categoryItemRepository->getActiveCategories();
        });
    }

    public function store(StoreCategoryItemDTO $dto): CategoryItem
    {
        $item = $this->categoryItemRepository->create($dto->toModelPayload());
        $this->clearCache();

        return $item;
    }

    public function update(CategoryItem $categoryItem, UpdateCategoryItemDTO $dto): CategoryItem
    {
        $item = $this->categoryItemRepository->update($categoryItem, $dto->toModelPayload());
        $this->clearCache();

        return $item;
    }

    public function delete(CategoryItem $categoryItem): bool
    {
        $result = $this->categoryItemRepository->delete($categoryItem);
        $this->clearCache();

        return $result;
    }

    public function hasItems(CategoryItem $categoryItem): bool
    {
        return $this->categoryItemRepository->hasItems($categoryItem);
    }

    private function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
