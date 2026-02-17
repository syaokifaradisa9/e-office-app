<?php

namespace Modules\Inventory\Repositories\ItemTransaction;

use Illuminate\Database\Eloquent\Collection;
use Modules\Inventory\Models\ItemTransaction;

class EloquentItemTransactionRepository implements ItemTransactionRepository
{
    public function getLatestTransactions(?int $divisionId = null, int $limit = 5): Collection
    {
        $query = ItemTransaction::with(['item:id,name', 'user:id,name']);

        if ($divisionId) {
            $query->whereHas('item', function ($q) use ($divisionId) {
                $q->where('division_id', $divisionId);
            });
        }

        return $query->latest()->limit($limit)->get();
    }

    public function create(array $data): ItemTransaction
    {
        return ItemTransaction::create($data);
    }
}
