<?php

namespace Modules\Inventory\Services;

use App\Repositories\Division\DivisionRepository;
use App\Repositories\User\UserRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\Inventory\Repositories\CategoryItem\CategoryItemRepository;
use Modules\Inventory\Repositories\Item\ItemRepository;

class LookupService
{
    public function __construct(
        private DivisionRepository $divisionRepository,
        private UserRepository $userRepository,
        private ItemRepository $itemRepository,
        private CategoryItemRepository $categoryRepository
    ) {}

    public function getActiveDivisions(): Collection
    {
        return $this->divisionRepository->findBy(['is_active' => true], ['id', 'name']);
    }

    public function getActiveUsers(): Collection
    {
        return $this->userRepository->findBy(['is_active' => true], ['id', 'name']);
    }

    public function getActiveCategories(): Collection
    {
        return $this->categoryRepository->getActiveCategories();
    }

    public function getBaseUnitItems(): Collection
    {
        return $this->itemRepository->getBaseUnits();
    }

    public function getWarehouseItemsWithStock(): Collection
    {
        return $this->itemRepository->getWarehouseItemsWithStock();
    }

    public function getItemsForOrder(array $orderedQuantities = [], bool $isRejected = false): Collection
    {
        $items = $this->itemRepository->getWarehouseItemsWithStock();

        if ($isRejected) {
            return $items;
        }

        return $items->map(function ($item) use ($orderedQuantities) {
            if (isset($orderedQuantities[$item->id])) {
                $item->stock += $orderedQuantities[$item->id];
            }
            return $item;
        });
    }

    public function getDivisionName(?int $divisionId): ?string
    {
        if (!$divisionId) return null;
        $division = $this->divisionRepository->find($divisionId, ['id', 'name']);
        return $division?->name;
    }
}
