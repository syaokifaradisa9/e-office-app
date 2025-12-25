<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Inventory\Enums\InventoryPermission;
use Modules\Inventory\Enums\WarehouseOrderStatus;
use Modules\Inventory\Models\Item;
use Modules\Inventory\Models\ItemTransaction;
use Modules\Inventory\Models\StockOpname;
use Modules\Inventory\Models\WarehouseOrder;

class DashboardController extends Controller
{
    /**
     * Main dashboard - redirect based on permission
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Priority: Main Warehouse > Division Warehouse > Default
        if ($user->can(InventoryPermission::ViewMainWarehouseDashboard->value)) {
            return redirect()->route('inventory.dashboard.main-warehouse');
        }

        if ($user->can(InventoryPermission::ViewDivisionWarehouseDashboard->value)) {
            return redirect()->route('inventory.dashboard.division-warehouse');
        }

        // Fallback - show generic dashboard with all data
        return $this->renderGenericDashboard();
    }

    /**
     * Dashboard Gudang Utama
     */
    public function mainWarehouse(Request $request)
    {
        $user = $request->user();

        if (! $user->can(InventoryPermission::ViewMainWarehouseDashboard->value)) {
            abort(403);
        }

        // Statistik order per status (keseluruhan)
        $statistics = WarehouseOrder::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->status->value => $item->total];
            });

        // 10 Order Pending
        $pendingOrders = WarehouseOrder::where('status', WarehouseOrderStatus::Pending)
            ->with(['user', 'division'])
            ->withCount('carts')
            ->withSum('carts', 'quantity')
            ->latest()
            ->limit(10)
            ->get();

        // 10 Order Confirmed (siap untuk delivery)
        $confirmedOrders = WarehouseOrder::where('status', WarehouseOrderStatus::Confirmed)
            ->with(['user', 'division'])
            ->withCount('carts')
            ->withSum('carts', 'quantity')
            ->latest()
            ->limit(10)
            ->get();

        return Inertia::render('Inventory/Dashboard/MainWarehouse', [
            'statistics' => $statistics,
            'pendingOrders' => $pendingOrders,
            'confirmedOrders' => $confirmedOrders,
        ]);
    }

    /**
     * Dashboard Gudang Divisi
     */
    public function divisionWarehouse(Request $request)
    {
        $user = $request->user();

        if (! $user->can(InventoryPermission::ViewDivisionWarehouseDashboard->value)) {
            abort(403);
        }

        if (! $user->division_id) {
            return Inertia::render('Inventory/Dashboard/DivisionWarehouse', [
                'error' => 'User tidak terdaftar di divisi manapun.',
            ]);
        }

        $divisionId = $user->division_id;

        // 5 Barang dengan stok terbanyak
        $mostStockItems = Item::where('division_id', $divisionId)
            ->orderByDesc('stock')
            ->limit(5)
            ->get(['id', 'name', 'stock', 'unit_of_measure']);

        // 5 Barang dengan stok tersedikit
        $leastStockItems = Item::where('division_id', $divisionId)
            ->where('stock', '>', 0)
            ->orderBy('stock')
            ->limit(5)
            ->get(['id', 'name', 'stock', 'unit_of_measure']);

        // Cek apakah sudah stock opname bulan ini
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $hasStockOpnameThisMonth = StockOpname::where('division_id', $divisionId)
            ->whereMonth('opname_date', $currentMonth)
            ->whereYear('opname_date', $currentYear)
            ->exists();

        // Permintaan barang aktif (belum selesai)
        $activeOrders = WarehouseOrder::where('division_id', $divisionId)
            ->whereNotIn('status', [WarehouseOrderStatus::Finished, WarehouseOrderStatus::Rejected])
            ->with(['user'])
            ->withCount('carts')
            ->withSum('carts', 'quantity')
            ->latest()
            ->limit(5)
            ->get();

        // Transaksi barang terbaru
        $recentTransactions = ItemTransaction::whereHas('item', function ($query) use ($divisionId) {
                $query->where('division_id', $divisionId);
            })
            ->with(['item:id,name', 'user:id,name'])
            ->latest()
            ->limit(5)
            ->get();

        return Inertia::render('Inventory/Dashboard/DivisionWarehouse', [
            'mostStockItems' => $mostStockItems,
            'leastStockItems' => $leastStockItems,
            'hasStockOpnameThisMonth' => $hasStockOpnameThisMonth,
            'activeOrders' => $activeOrders,
            'recentTransactions' => $recentTransactions,
            'divisionName' => $user->division?->name,
        ]);
    }

    /**
     * Generic dashboard for users without specific permissions
     */
    private function renderGenericDashboard()
    {
        $statistics = WarehouseOrder::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->status->value => $item->total];
            });

        $pendingOrders = WarehouseOrder::where('status', WarehouseOrderStatus::Pending)
            ->with(['user', 'division'])
            ->withCount('carts')
            ->withSum('carts', 'quantity')
            ->latest()
            ->limit(10)
            ->get();

        $confirmedOrders = WarehouseOrder::where('status', WarehouseOrderStatus::Confirmed)
            ->with(['user', 'division'])
            ->withCount('carts')
            ->withSum('carts', 'quantity')
            ->latest()
            ->limit(10)
            ->get();

        $deliveredOrders = WarehouseOrder::where('status', WarehouseOrderStatus::Delivered)
            ->with(['user', 'division'])
            ->withCount('carts')
            ->withSum('carts', 'quantity')
            ->latest()
            ->limit(10)
            ->get();

        return Inertia::render('Inventory/Dashboard/Index', [
            'statistics' => $statistics,
            'pendingOrders' => $pendingOrders,
            'confirmedOrders' => $confirmedOrders,
            'deliveredOrders' => $deliveredOrders,
        ]);
    }
}
