<?php

namespace Modules\Inventory\Services;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Enums\ItemTransactionType;
use Modules\Inventory\Models\Item;
use Modules\Inventory\Repositories\Item\ItemRepository;
use Modules\Inventory\Repositories\ItemTransaction\ItemTransactionRepository;

class StockConversionService
{
    public function __construct(
        private ItemRepository $itemRepository,
        private ItemTransactionRepository $transactionRepository
    ) {}

    /**
     * Convert stock from pack/box to units
     */
    public function convertStock(Item $sourceItem, int $quantityToConvert, User $user): void
    {
        // 1. Validation
        if ($quantityToConvert <= 0) {
            throw new Exception('Jumlah konversi harus lebih dari 0.');
        }

        if ($sourceItem->stock < $quantityToConvert) {
            throw new Exception('Stok tidak mencukupi untuk konversi.');
        }

        if ($sourceItem->division_id != $user->division_id) {
            if ($sourceItem->division_id !== null) {
                throw new Exception('Anda tidak memiliki akses untuk mengonversi stok ini.');
            }
        }

        if ($sourceItem->multiplier <= 1) {
            throw new Exception('Item ini bukan merupakan item pack/box (multiplier <= 1).');
        }

        $quantityToAdd = $quantityToConvert * $sourceItem->multiplier;

        DB::transaction(function () use ($sourceItem, $quantityToConvert, $quantityToAdd, $user) {
            $targetItem = null;

            // 2. Check Reference Item
            if ($sourceItem->reference_item_id) {
                $targetItem = $this->itemRepository->findById($sourceItem->reference_item_id);
            } else {
                if ($sourceItem->division_id === null) {
                    throw new Exception('Item referensi tidak ditemukan. Silakan set reference_item_id terlebih dahulu pada data item.');
                }

                if (! $sourceItem->main_reference_item_id) {
                    throw new Exception('Item ini tidak memiliki referensi ke Gudang Utama.');
                }

                $mainSourceItem = $this->itemRepository->findById($sourceItem->main_reference_item_id);

                if (! $mainSourceItem || ! $mainSourceItem->reference_item_id) {
                    throw new Exception('Item referensi tidak ditemukan di Gudang Utama.');
                }

                $mainTargetItem = $this->itemRepository->findById($mainSourceItem->reference_item_id);

                if (! $mainTargetItem) {
                    throw new Exception('Item target referensi tidak ditemukan di Gudang Utama.');
                }

                // Create Item (Clone to Division)
                $targetItem = $this->itemRepository->create([
                    'division_id' => $user->division_id,
                    'category_id' => $mainTargetItem->category_id,
                    'image_url' => $mainTargetItem->image_url,
                    'name' => $mainTargetItem->name,
                    'unit_of_measure' => $mainTargetItem->unit_of_measure,
                    'stock' => 0,
                    'description' => $mainTargetItem->description,
                    'reference_item_id' => null,
                    'main_reference_item_id' => $mainTargetItem->id,
                    'multiplier' => $mainTargetItem->multiplier,
                ]);

                // Update Link Reference on Source Item
                $this->itemRepository->update($sourceItem, ['reference_item_id' => $targetItem->id]);
            }

            if (! $targetItem) {
                throw new Exception('Gagal mendapatkan item target konversi.');
            }

            // 3. Update Stocks
            $this->itemRepository->update($sourceItem, ['stock' => $sourceItem->stock - $quantityToConvert]);
            $this->itemRepository->update($targetItem, ['stock' => $targetItem->stock + $quantityToAdd]);

            // 4. Transaction Logging
            $date = now();

            $this->transactionRepository->create([
                'date' => $date,
                'type' => ItemTransactionType::ConversionOut,
                'item_id' => $sourceItem->id,
                'quantity' => $quantityToConvert,
                'user_id' => $user->id,
                'description' => 'Konversi ke '.$targetItem->name,
            ]);

            $this->transactionRepository->create([
                'date' => $date,
                'type' => ItemTransactionType::ConversionIn,
                'item_id' => $targetItem->id,
                'quantity' => $quantityToAdd,
                'user_id' => $user->id,
                'description' => 'Konversi dari '.$sourceItem->name,
            ]);
        });
    }
}
