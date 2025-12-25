<?php

namespace Modules\Inventory\Repositories\WarehouseOrder;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Enums\WarehouseOrderStatus;
use Modules\Inventory\Models\WarehouseOrder;

class EloquentWarehouseOrderRepository implements WarehouseOrderRepository
{
    public function getActiveOrders(?int $divisionId = null, int $limit = 5): Collection
    {
        $query = WarehouseOrder::whereNotIn('status', [WarehouseOrderStatus::Finished, WarehouseOrderStatus::Rejected])
            ->with(['user:id,name']);

        if ($divisionId) {
            $query->where('division_id', $divisionId);
        } else {
            $query->with('division:id,name');
        }

        return $query->withCount('carts')
            ->withSum('carts', 'quantity')
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getPendingOrders(int $limit = 10): Collection
    {
        return WarehouseOrder::where('status', WarehouseOrderStatus::Pending)
            ->with(['user', 'division'])
            ->withCount('carts')
            ->withSum('carts', 'quantity')
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getConfirmedOrders(int $limit = 10): Collection
    {
        return WarehouseOrder::where('status', WarehouseOrderStatus::Confirmed)
            ->with(['user', 'division'])
            ->withCount('carts')
            ->withSum('carts', 'quantity')
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getStatusStatistics(): Collection
    {
        return WarehouseOrder::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get();
    }

    public function create(array $data): WarehouseOrder
    {
        return WarehouseOrder::create($data);
    }

    public function update(WarehouseOrder $order, array $data): WarehouseOrder
    {
        $order->update($data);
        return $order->refresh();
    }
}
