<?php

namespace Modules\Inventory\Repositories\ItemTransaction;

use Illuminate\Database\Eloquent\Collection;
use Modules\Inventory\Models\ItemTransaction;

interface ItemTransactionRepository
{
    public function getLatestTransactions(?int $divisionId = null, int $limit = 5): Collection;

    public function create(array $data): ItemTransaction;
}
