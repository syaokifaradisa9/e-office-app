<?php

namespace Modules\Inventory\Repositories\WarehouseOrder;

use Illuminate\Database\Eloquent\Collection;
use Modules\Inventory\Models\WarehouseOrder;

interface WarehouseOrderRepository
{
    public function getActiveOrders(?int $divisionId = null, int $limit = 5): Collection;

    public function getPendingOrders(int $limit = 10): Collection;

    public function getConfirmedOrders(int $limit = 10): Collection;

    public function getStatusStatistics(): Collection;

    public function create(array $data): WarehouseOrder;

    public function update(WarehouseOrder $order, array $data): WarehouseOrder;
    
    public function generateOrderNumber(): string;
}
