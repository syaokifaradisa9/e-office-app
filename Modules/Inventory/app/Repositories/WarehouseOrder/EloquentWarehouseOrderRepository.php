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

    public function generateOrderNumber(): string
    {
        $year = date('y');
        $month = date('m');
        
        $latestOrder = WarehouseOrder::where('order_number', 'like', $year . '%')
            ->orderBy('order_number', 'desc')
            ->first();

        if (!$latestOrder) {
            $sequence = 1;
        } else {
            // Extract the last 4 digits from the order number
            $lastSequence = substr($latestOrder->order_number, 4);
            $sequence = intval($lastSequence) + 1;
        }

        return $year . $month . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}
