<?php

namespace App\Http\Controllers;

use App\Models\Division;
use App\Models\Position;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $statistics = [];
        $dashboardData = [];

        // Total Divisi - jika user punya permission lihat_divisi
        if ($user->can('lihat_divisi')) {
            $statistics['total_divisions'] = Division::count();
        }

        // Total Jabatan - jika user punya permission lihat_jabatan
        if ($user->can('lihat_jabatan')) {
            $statistics['total_positions'] = Position::count();
        }

        // Total Pegawai - jika user punya permission lihat_pengguna
        if ($user->can('lihat_pengguna')) {
            $statistics['total_employees'] = User::count();
        }

        // Total Role - jika user punya permission lihat_role
        if ($user->can('lihat_role')) {
            $statistics['total_roles'] = Role::count();
        }

        // ========================================
        // Module: Inventory
        // ========================================
        // if (File::isDirectory(base_path('Modules/Inventory'))) {
        //     $inventoryService = app(\Modules\Inventory\Services\InventoryDashboardService::class);
        //     $dashboardData['inventory'] = $inventoryService->getDashboardTabs();
        // }

        // ========================================
        // Module: Archieve
        // ========================================
        if (File::isDirectory(base_path('Modules/Archieve'))) {
            $archieveService = app(\Modules\Archieve\Services\ArchieveDashboardService::class);
            $dashboardData['archieve'] = $archieveService->getDashboardTabs();
        }

        // ========================================
        // Module: VisitorManagement
        // ========================================
        if (File::isDirectory(base_path('Modules/VisitorManagement'))) {
            $visitorService = app(\Modules\VisitorManagement\Services\VisitorDashboardService::class);
            $dashboardData['visitor'] = $visitorService->getDashboardTabs();
        }

        return Inertia::render('Dashboard', [
            'statistics' => $statistics,
            'dashboardData' => $dashboardData,
        ]);
    }
}
