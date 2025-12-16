<?php

namespace Modules\Inventory\Services;

use App\Models\Division;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Enums\WarehouseOrderStatus;
use Modules\Inventory\Models\Item;
use Modules\Inventory\Models\WarehouseOrder;
use Modules\Inventory\Models\WarehouseOrderCart;

class ReportService
{
    public function getReportData(User $user): array
    {
        return [
            'overall' => $this->getOverallData(),
            'division' => $this->getDivisionData(),
            'alerts' => $this->getAlertsData(),
        ];
    }

    private function getOverallData(): array
    {
        return [
            'stock_extremes' => $this->getOverallStockExtremes(),
            'out_of_stock' => $this->getOutOfStockItems(),
            'request_extremes' => $this->getOverallItemRequestExtremes(),
            'monthly_requests' => $this->getOverallMonthlyRequests(),
            'stock_by_category' => $this->getStockByCategory(),
            'dead_stock' => $this->getDeadStock(),
            'stock_turnover' => $this->getStockTurnover(),
            'order_status_stats' => $this->getOrderStatusStats(),
            'efficiency_stats' => $this->getEfficiencyStats(),
            'reorder_recommendations' => $this->getReorderRecommendations(),
        ];
    }

    private function getDivisionData(?string $divisionId = null): array
    {
        return [
            'stock_extremes' => $this->getDivisionStockExtremes($divisionId),
            'request_extremes' => $this->getDivisionItemRequestExtremes($divisionId),
            'monthly_item_requests' => $this->getDivisionMonthlyItemRequests($divisionId),
            'monthly_order_requests' => $this->getDivisionMonthlyOrderRequests($divisionId),
            'lead_time_analysis' => $this->getLeadTimeAnalysis($divisionId),
            'top_requesters' => $this->getTopRequesters($divisionId),
            'order_status_stats' => $this->getOrderStatusStats($divisionId),
        ];
    }

    private function getAlertsData(?string $divisionId = null): array
    {
        return [
            'critical_stock' => $this->getCriticalStock($divisionId),
            'stock_out_frequency' => $this->getStockOutFrequency($divisionId),
            'fulfillment_rate' => $this->getFulfillmentRate($divisionId),
        ];
    }

    private function getOverallStockExtremes(): array
    {
        return [
            'most' => Item::where('stock', '>', 0)->orderByDesc('stock')->limit(10)->with('division')->get(),
            'least' => Item::where('stock', '>', 0)->orderBy('stock')->limit(10)->with('division')->get(),
        ];
    }

    private function getOutOfStockItems()
    {
        return Item::where('stock', '<=', 0)->limit(10)->with('division')->get();
    }

    private function getOverallItemRequestExtremes(): array
    {
        $query = WarehouseOrderCart::query()
            ->select('item_id', DB::raw('SUM(quantity) as total_quantity'))
            ->join('warehouse_orders', 'warehouse_order_carts.warehouse_order_id', '=', 'warehouse_orders.id')
            ->where('warehouse_orders.status', WarehouseOrderStatus::Finished)
            ->groupBy('item_id')
            ->with('item');

        return [
            'most' => (clone $query)->orderByDesc('total_quantity')->limit(10)->get()->map(fn ($i) => ['name' => $i->item->name ?? 'Unknown', 'total' => $i->total_quantity]),
            'least' => (clone $query)->orderBy('total_quantity')->limit(10)->get()->map(fn ($i) => ['name' => $i->item->name ?? 'Unknown', 'total' => $i->total_quantity]),
        ];
    }

    private function getOverallMonthlyRequests()
    {
        return WarehouseOrder::query()
            ->leftJoin('warehouse_order_carts', 'warehouse_orders.id', '=', 'warehouse_order_carts.warehouse_order_id')
            ->select(
                DB::raw('DATE_FORMAT(warehouse_orders.created_at, "%Y-%m") as month'),
                DB::raw('count(distinct warehouse_orders.id) as total_orders'),
                DB::raw('coalesce(sum(warehouse_order_carts.quantity), 0) as total_items')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();
    }

    private function getStockByCategory()
    {
        return Item::query()
            ->join('category_items', 'items.category_id', '=', 'category_items.id')
            ->select('category_items.name', DB::raw('SUM(items.stock) as total_stock'))
            ->groupBy('category_items.name')
            ->get();
    }

    private function getDeadStock()
    {
        $threeMonthsAgo = now()->subMonths(3);

        return Item::whereNull('division_id')
            ->whereDoesntHave('warehouseOrderCarts', function ($query) use ($threeMonthsAgo) {
                $query->whereHas('warehouseOrder', function ($q) use ($threeMonthsAgo) {
                    $q->where('created_at', '>=', $threeMonthsAgo);
                });
            })
            ->limit(20)
            ->get();
    }

    private function getStockTurnover()
    {
        $result = Item::whereNull('items.division_id')
            ->select(
                'items.id',
                'items.name',
                'items.stock',
                'items.unit_of_measure',
                DB::raw('COALESCE(SUM(warehouse_order_carts.quantity), 0) as total_requested')
            )
            ->leftJoin('warehouse_order_carts', 'items.id', '=', 'warehouse_order_carts.item_id')
            ->leftJoin('warehouse_orders', 'warehouse_order_carts.warehouse_order_id', '=', 'warehouse_orders.id')
            ->where(function ($query) {
                $query->where('warehouse_orders.created_at', '>=', now()->subMonths(3))
                    ->orWhereNull('warehouse_orders.id');
            })
            ->groupBy('items.id', 'items.name', 'items.stock', 'items.unit_of_measure')
            ->get()
            ->map(function ($item) {
                $turnoverRatio = $item->stock > 0 ? round($item->total_requested / $item->stock, 2) : 0;

                return [
                    'name' => $item->name,
                    'stock' => $item->stock,
                    'unit_of_measure' => $item->unit_of_measure,
                    'total_requested' => $item->total_requested,
                    'turnover_ratio' => $turnoverRatio,
                ];
            })
            ->sortByDesc('turnover_ratio')
            ->take(10)
            ->values();

        return $result;
    }

    private function getCriticalStock(?string $divisionId)
    {
        $query = Item::where('stock', '>', 0)
            ->where('stock', '<=', 10);

        if ($divisionId) {
            $query->where('division_id', $divisionId);
        } else {
            $query->whereNull('division_id');
        }

        return $query->with('category')->limit(20)->get();
    }

    private function getStockOutFrequency(?string $divisionId): array
    {
        $query = Item::where('stock', '<=', 0);

        if ($divisionId) {
            $query->where('division_id', $divisionId);
        } else {
            $query->whereNull('division_id');
        }

        $outOfStockItems = $query->with('category')->get();

        return [
            'out_of_stock_items' => $outOfStockItems,
            'unfulfilled_requests' => [],
        ];
    }

    private function getFulfillmentRate(?string $divisionId): array
    {
        $query = WarehouseOrder::query();

        if ($divisionId) {
            $query->where('division_id', $divisionId);
        }

        $totalOrders = (clone $query)->count();
        $finishedOrders = (clone $query)->where('status', WarehouseOrderStatus::Finished)->count();
        $pendingOrders = (clone $query)->where('status', WarehouseOrderStatus::Pending)->count();
        $deliveredOrders = (clone $query)->whereIn('status', [
            WarehouseOrderStatus::Delivered,
            WarehouseOrderStatus::Delivery,
        ])->count();

        $fulfillmentRate = $totalOrders > 0 ? round(($finishedOrders / $totalOrders) * 100, 2) : 0;

        return [
            'total_orders' => $totalOrders,
            'finished_orders' => $finishedOrders,
            'pending_orders' => $pendingOrders,
            'delivered_orders' => $deliveredOrders,
            'fulfillment_rate' => $fulfillmentRate,
        ];
    }

    private function getDivisionStockExtremes(?string $divisionId): array
    {
        $divisions = $divisionId ? Division::where('id', $divisionId)->get() : Division::all();
        $result = [];

        foreach ($divisions as $division) {
            $most = Item::where('division_id', $division->id)->where('stock', '>', 0)->orderByDesc('stock')->limit(5)->get();
            $least = Item::where('division_id', $division->id)->where('stock', '>', 0)->orderBy('stock')->limit(5)->get();
            $outOfStock = Item::where('division_id', $division->id)->where('stock', '<=', 0)->limit(5)->get();

            if ($most->isNotEmpty() || $least->isNotEmpty() || $outOfStock->isNotEmpty()) {
                $result[] = [
                    'division_name' => $division->name,
                    'most' => $most,
                    'least' => $least,
                    'out_of_stock' => $outOfStock,
                ];
            }
        }

        return $result;
    }

    private function getDivisionItemRequestExtremes(?string $divisionId): array
    {
        $divisions = $divisionId ? Division::where('id', $divisionId)->get() : Division::all();
        $result = [];

        foreach ($divisions as $division) {
            $query = WarehouseOrderCart::query()
                ->select('item_id', DB::raw('SUM(quantity) as total_quantity'))
                ->join('warehouse_orders', 'warehouse_order_carts.warehouse_order_id', '=', 'warehouse_orders.id')
                ->where('warehouse_orders.status', WarehouseOrderStatus::Finished)
                ->where('warehouse_orders.division_id', $division->id)
                ->groupBy('item_id')
                ->with('item');

            $most = (clone $query)->orderByDesc('total_quantity')->limit(5)->get()->map(fn ($i) => ['name' => $i->item->name ?? 'Unknown', 'total' => $i->total_quantity]);
            $least = (clone $query)->orderBy('total_quantity')->limit(5)->get()->map(fn ($i) => ['name' => $i->item->name ?? 'Unknown', 'total' => $i->total_quantity]);

            if ($most->isNotEmpty() || $least->isNotEmpty()) {
                $result[] = [
                    'division_name' => $division->name,
                    'most' => $most,
                    'least' => $least,
                ];
            }
        }

        return $result;
    }

    private function getDivisionMonthlyItemRequests(?string $divisionId)
    {
        $query = WarehouseOrderCart::query()
            ->join('warehouse_orders', 'warehouse_order_carts.warehouse_order_id', '=', 'warehouse_orders.id')
            ->join('divisions', 'warehouse_orders.division_id', '=', 'divisions.id')
            ->select(
                'divisions.name as division_name',
                DB::raw('DATE_FORMAT(warehouse_orders.created_at, "%Y-%m") as month'),
                DB::raw('SUM(warehouse_order_carts.quantity) as total_quantity')
            )
            ->groupBy('divisions.name', 'month')
            ->orderBy('month');

        if ($divisionId) {
            $query->where('warehouse_orders.division_id', $divisionId);
        }

        return $query->get()->groupBy('division_name');
    }

    private function getDivisionMonthlyOrderRequests(?string $divisionId)
    {
        $query = WarehouseOrder::query()
            ->join('divisions', 'warehouse_orders.division_id', '=', 'divisions.id')
            ->select(
                'divisions.name as division_name',
                DB::raw('DATE_FORMAT(warehouse_orders.created_at, "%Y-%m") as month'),
                DB::raw('count(*) as total_orders')
            )
            ->groupBy('divisions.name', 'month')
            ->orderBy('month');

        if ($divisionId) {
            $query->where('warehouse_orders.division_id', $divisionId);
        }

        return $query->get()->groupBy('division_name');
    }

    private function getLeadTimeAnalysis(?string $divisionId): array
    {
        $divisions = $divisionId ? Division::where('id', $divisionId)->get() : Division::all();
        $result = [];

        foreach ($divisions as $division) {
            $avgLeadTime = WarehouseOrder::where('division_id', $division->id)
                ->where('status', WarehouseOrderStatus::Finished)
                ->whereNotNull('receipt_date')
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, receipt_date)) as avg_hours')
                ->value('avg_hours');

            $avgApprovalTime = WarehouseOrder::where('division_id', $division->id)
                ->whereNotNull('accepted_date')
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, accepted_date)) as avg_hours')
                ->value('avg_hours');

            $avgDeliveryTime = WarehouseOrder::where('division_id', $division->id)
                ->where('status', WarehouseOrderStatus::Finished)
                ->whereNotNull('delivery_date')
                ->whereNotNull('accepted_date')
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, accepted_date, delivery_date)) as avg_hours')
                ->value('avg_hours');

            if ($avgLeadTime || $avgApprovalTime || $avgDeliveryTime) {
                $result[] = [
                    'division_name' => $division->name,
                    'avg_total_lead_time' => round($avgLeadTime ?? 0, 1),
                    'avg_approval_time' => round($avgApprovalTime ?? 0, 1),
                    'avg_delivery_time' => round($avgDeliveryTime ?? 0, 1),
                ];
            }
        }

        return $result;
    }

    private function getTopRequesters(?string $divisionId): array
    {
        $divisions = $divisionId ? Division::where('id', $divisionId)->get() : Division::all();
        $result = [];

        foreach ($divisions as $division) {
            $topUsers = WarehouseOrder::where('warehouse_orders.division_id', $division->id)
                ->join('users', 'warehouse_orders.user_id', '=', 'users.id')
                ->select(
                    'users.name',
                    DB::raw('COUNT(*) as total_requests'),
                    DB::raw('SUM(CASE WHEN warehouse_orders.status = "'.WarehouseOrderStatus::Finished->value.'" THEN 1 ELSE 0 END) as finished_requests')
                )
                ->groupBy('users.id', 'users.name')
                ->orderByDesc('total_requests')
                ->limit(5)
                ->get();

            if ($topUsers->isNotEmpty()) {
                $result[] = [
                    'division_name' => $division->name,
                    'top_users' => $topUsers,
                ];
            }
        }

        return $result;
    }

    private function getOrderStatusStats(?string $divisionId = null)
    {
        $query = WarehouseOrder::query()
            ->select('status', DB::raw('count(*) as total'));

        if ($divisionId) {
            $query->where('division_id', $divisionId);
        }

        return $query->groupBy('status')->get()->mapWithKeys(function ($item) {
            return [$item->status->value => $item->total];
        });
    }

    private function getEfficiencyStats(?string $divisionId = null): array
    {
        $query = WarehouseOrder::query();

        if ($divisionId) {
            $query->where('division_id', $divisionId);
        }

        $avgLeadTime = (clone $query)->where('status', WarehouseOrderStatus::Finished)
            ->whereNotNull('receipt_date')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, receipt_date)) as avg_hours')
            ->value('avg_hours');

        $avgApprovalTime = (clone $query)->whereNotNull('accepted_date')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, accepted_date)) as avg_hours')
            ->value('avg_hours');

        $avgDeliveryTime = (clone $query)->where('status', WarehouseOrderStatus::Finished)
            ->whereNotNull('delivery_date')
            ->whereNotNull('accepted_date')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, accepted_date, delivery_date)) as avg_hours')
            ->value('avg_hours');

        return [
            'avg_total_lead_time' => round($avgLeadTime ?? 0, 1),
            'avg_approval_time' => round($avgApprovalTime ?? 0, 1),
            'avg_delivery_time' => round($avgDeliveryTime ?? 0, 1),
        ];
    }

    private function getReorderRecommendations(?string $divisionId = null)
    {
        $query = Item::query()
            ->select(
                'items.id',
                'items.name',
                'items.stock',
                'items.unit_of_measure',
                DB::raw('COALESCE(SUM(warehouse_order_carts.quantity), 0) as total_requested')
            )
            ->leftJoin('warehouse_order_carts', 'items.id', '=', 'warehouse_order_carts.item_id')
            ->leftJoin('warehouse_orders', 'warehouse_order_carts.warehouse_order_id', '=', 'warehouse_orders.id')
            ->where('items.stock', '<=', 20)
            ->where('warehouse_orders.created_at', '>=', now()->subMonths(3))
            ->groupBy('items.id', 'items.name', 'items.stock', 'items.unit_of_measure')
            ->orderByDesc('total_requested')
            ->limit(10);

        if ($divisionId) {
            $query->where('items.division_id', $divisionId);
        } else {
            $query->whereNull('items.division_id');
        }

        return $query->get();
    }
}
