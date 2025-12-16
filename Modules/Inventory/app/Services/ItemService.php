<?php

namespace Modules\Inventory\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Enums\ItemTransactionType;
use Modules\Inventory\Models\Item;
use Modules\Inventory\Models\ItemTransaction;

class ItemService
{
    public function issueStock(Item $item, int $quantity, string $description, User $user): void
    {
        DB::transaction(function () use ($item, $quantity, $description, $user) {
            if ($item->stock < $quantity) {
                throw new \Exception('Stok tidak mencukupi');
            }

            $item->decrement('stock', $quantity);

            ItemTransaction::create([
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
