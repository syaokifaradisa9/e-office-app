<?php

namespace Modules\Inventory\Services;

use App\Models\Division;
use Carbon\Carbon;
use Modules\Inventory\Models\Item;
use Modules\Inventory\Models\ItemTransaction;
use Modules\Inventory\Models\StockOpname;
use Modules\Inventory\Models\WarehouseOrder;
use Modules\Inventory\Enums\WarehouseOrderStatus;

class InventoryDashboardService
{
    /**
     * Get inventory dashboard tabs for the authenticated user
     */
    public function getDashboardTabs(): array
    {
        $user = auth()->user();
        $tabs = [];

        // Tab: Gudang Divisi
        if ($user->can('lihat_dashboard_gudang_divisi') && $user->division_id) {
            $tabs[] = $this->getDivisionWarehouseTab($user);
        }

        // Tab: Gudang Utama
        if ($user->can('lihat_dashboard_gudang_utama')) {
            $tabs[] = $this->getMainWarehouseTab();
        }

        // Tab: Gudang Keseluruhan
        if ($user->can('lihat_dashboard_gudang_keseluruhan')) {
            $tabs[] = $this->getAllWarehouseTab();
        }

        return $tabs;
    }

    /**
     * Get division warehouse tab data
     */
    private function getDivisionWarehouseTab($user): array
    {
        $divisionId = $user->division_id;
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        return [
            'id' => 'division',
            'label' => 'Gudang ' . ($user->division?->name ?? 'Divisi'),
            'icon' => 'building',
            'type' => 'warehouse',
            'stock_opname_link' => '/inventory/stock-opname/division',
            'data' => [
                'most_stock_items' => Item::where('division_id', $divisionId)
                    ->orderByDesc('stock')
                    ->limit(5)
                    ->get(['id', 'name', 'stock', 'unit_of_measure']),
                
                'least_stock_items' => Item::where('division_id', $divisionId)
                    ->where('stock', '>', 0)
                    ->orderBy('stock')
                    ->limit(5)
                    ->get(['id', 'name', 'stock', 'unit_of_measure']),
                
                'has_stock_opname_this_month' => StockOpname::where('division_id', $divisionId)
                    ->whereMonth('opname_date', $currentMonth)
                    ->whereYear('opname_date', $currentYear)
                    ->exists(),
                
                'active_orders' => WarehouseOrder::where('division_id', $divisionId)
                    ->whereNotIn('status', [WarehouseOrderStatus::Finished, WarehouseOrderStatus::Rejected])
                    ->with(['user:id,name'])
                    ->withCount('carts')
                    ->withSum('carts', 'quantity')
                    ->latest()
                    ->limit(5)
                    ->get(),
                
                'recent_transactions' => ItemTransaction::whereHas('item', function ($query) use ($divisionId) {
                        $query->where('division_id', $divisionId);
                    })
                    ->with(['item:id,name', 'user:id,name'])
                    ->latest()
                    ->limit(5)
                    ->get(),
            ],
        ];
    }

    /**
     * Get main warehouse tab data
     */
    private function getMainWarehouseTab(): array
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        return [
            'id' => 'main',
            'label' => 'Gudang Utama',
            'icon' => 'warehouse',
            'type' => 'warehouse',
            'stock_opname_link' => '/inventory/stock-opname/warehouse',
            'data' => [
                'most_stock_items' => Item::whereNull('division_id')
                    ->orderByDesc('stock')
                    ->limit(5)
                    ->get(['id', 'name', 'stock', 'unit_of_measure']),
                
                'least_stock_items' => Item::whereNull('division_id')
                    ->where('stock', '>', 0)
                    ->orderBy('stock')
                    ->limit(5)
                    ->get(['id', 'name', 'stock', 'unit_of_measure']),
                
                'has_stock_opname_this_month' => StockOpname::whereNull('division_id')
                    ->whereMonth('opname_date', $currentMonth)
                    ->whereYear('opname_date', $currentYear)
                    ->exists(),
                
                'active_orders' => WarehouseOrder::whereNotIn('status', [WarehouseOrderStatus::Finished, WarehouseOrderStatus::Rejected])
                    ->with(['user:id,name', 'division:id,name'])
                    ->withCount('carts')
                    ->withSum('carts', 'quantity')
                    ->latest()
                    ->limit(5)
                    ->get(),
                
                'recent_transactions' => ItemTransaction::whereHas('item', function ($query) {
                        $query->whereNull('division_id');
                    })
                    ->with(['item:id,name', 'user:id,name'])
                    ->latest()
                    ->limit(5)
                    ->get(),
            ],
        ];
    }

    /**
     * Get all warehouse tab data (keseluruhan)
     */
    private function getAllWarehouseTab(): array
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        // Get all divisions with their stock opname status
        $divisions = Division::orderBy('name')->get(['id', 'name']);
        $stockOpnameStatus = [];

        foreach ($divisions as $division) {
            $hasOpname = StockOpname::where('division_id', $division->id)
                ->whereMonth('opname_date', $currentMonth)
                ->whereYear('opname_date', $currentYear)
                ->exists();

            $stockOpnameStatus[] = [
                'division_id' => $division->id,
                'division_name' => $division->name,
                'has_stock_opname' => $hasOpname,
            ];
        }

        // Add main warehouse status
        $hasMainOpname = StockOpname::whereNull('division_id')
            ->whereMonth('opname_date', $currentMonth)
            ->whereYear('opname_date', $currentYear)
            ->exists();

        array_unshift($stockOpnameStatus, [
            'division_id' => null,
            'division_name' => 'Gudang Utama',
            'has_stock_opname' => $hasMainOpname,
        ]);

        return [
            'id' => 'all',
            'label' => 'Keseluruhan',
            'icon' => 'globe',
            'type' => 'overview',
            'data' => [
                'recent_transactions' => ItemTransaction::with(['item:id,name,division_id', 'item.division:id,name', 'user:id,name'])
                    ->latest()
                    ->limit(10)
                    ->get(),
                
                'recent_orders' => WarehouseOrder::with(['user:id,name', 'division:id,name'])
                    ->withCount('carts')
                    ->withSum('carts', 'quantity')
                    ->latest()
                    ->limit(5)
                    ->get(),
                
                'stock_opname_status' => $stockOpnameStatus,
            ],
        ];
    }
}
