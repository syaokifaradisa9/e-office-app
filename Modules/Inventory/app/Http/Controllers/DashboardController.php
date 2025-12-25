<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Inventory\Enums\InventoryPermission;
use Modules\Inventory\Services\InventoryDashboardService;

class DashboardController extends Controller
{
    public function __construct(
        private InventoryDashboardService $dashboardService
    ) {}

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
        if (! $request->user()->can(InventoryPermission::ViewMainWarehouseDashboard->value)) {
            abort(403);
        }

        $data = $this->dashboardService->getMainWarehouseData();

        return Inertia::render('Inventory/Dashboard/MainWarehouse', $data);
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

        $data = $this->dashboardService->getDivisionWarehouseData($user);

        return Inertia::render('Inventory/Dashboard/DivisionWarehouse', $data);
    }

    /**
     * Generic dashboard for users without specific permissions
     */
    private function renderGenericDashboard()
    {
        $data = $this->dashboardService->getGenericDashboardData();

        return Inertia::render('Inventory/Dashboard/Index', $data);
    }
}
