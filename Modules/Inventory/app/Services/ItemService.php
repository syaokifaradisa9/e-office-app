<?php

namespace Modules\Inventory\Services;

use Exception;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\Item;
use Illuminate\Database\Eloquent\Collection;
use Modules\Inventory\Enums\ItemTransactionType;
use Modules\Inventory\DataTransferObjects\ItemDTO;
use Modules\Inventory\Repositories\Item\ItemRepository;
use Modules\Inventory\Repositories\CategoryItem\CategoryItemRepository;
use Modules\Inventory\Repositories\ItemTransaction\ItemTransactionRepository;

class ItemService
{
    public function __construct(
        private ItemRepository $itemRepository,
        private ItemTransactionRepository $transactionRepository,
        private CategoryItemRepository $categoryRepository
    ) {}

    public function getActiveCategories(): Collection
    {
        return $this->categoryRepository->getActiveCategories();
    }

    public function getBaseUnits(): Collection
    {
        return $this->itemRepository->getBaseUnits();
    }

    public function getConversionTargets(Item $item): Collection
    {
        return $this->itemRepository->getConversionTargets($item);
    }

    public function store(ItemDTO $dto): Item
    {
        return $this->itemRepository->create($dto->toArray());
    }

    public function update(Item $item, ItemDTO $dto): Item
    {
        return $this->itemRepository->update($item, $dto->toArray());
    }

    public function delete(Item $item): bool
    {
        return $this->itemRepository->delete($item);
    }

    public function issueStock(Item $item, int $quantity, string $description, User $user): void
    {
        if ($quantity <= 0) {
            throw new Exception('Jumlah pengeluaran harus lebih besar dari nol');
        }

        DB::transaction(function () use ($item, $quantity, $description, $user) {
            if ($item->stock < $quantity) {
                throw new Exception('Stok tidak mencukupi');
            }

            $this->itemRepository->update($item, [
                'stock' => $item->stock - $quantity
            ]);

            $this->transactionRepository->create([
                'date' => now(),
                'type' => ItemTransactionType::Out,
                'item_id' => $item->id,
                'quantity' => $quantity,
                'user_id' => $user->id,
                'description' => $description,
            ]);
        });
    }
}
