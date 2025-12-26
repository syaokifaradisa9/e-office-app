<?php

namespace Modules\Archieve\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Modules\Archieve\Services\ArchieveDashboardService;
use Modules\Archieve\Enums\ArchieveUserPermission;

class DashboardController extends Controller
{
    public function __construct(
        private ArchieveDashboardService $dashboardService
    ) {}

    public function index()
    {
        $user = auth()->user();

        // Check if user has any dashboard permission
        $hasAnyPermission = $user->can(ArchieveUserPermission::ViewDashboardDivision->value) ||
                           $user->can(ArchieveUserPermission::ViewDashboardAll->value);

        if (!$hasAnyPermission) {
            abort(403, 'Anda tidak memiliki akses ke dashboard arsip.');
        }

        $tabs = $this->dashboardService->getDashboardTabs();

        return Inertia::render('Archieve/Dashboard/Index', [
            'tabs' => $tabs,
            'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
        ]);
    }
}
