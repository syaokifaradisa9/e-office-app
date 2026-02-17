<?php

namespace Modules\VisitorManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Modules\VisitorManagement\Services\VisitorDashboardService;
use Modules\VisitorManagement\Enums\VisitorUserPermission;

class VisitorDashboardController extends Controller
{
    public function __construct(
        private VisitorDashboardService $dashboardService
    ) {}

    public function index()
    {
        if (!auth()->user()->can(VisitorUserPermission::ViewDashboard->value)) {
            abort(403);
        }

        return Inertia::render('VisitorManagement/Visitor/Dashboard', [
            'stats' => $this->dashboardService->getStatistics(),
            'purposeDistribution' => $this->dashboardService->getPurposeDistribution(),
            'weeklyTrend' => $this->dashboardService->getWeeklyTrend(),
        ]);
    }
}
