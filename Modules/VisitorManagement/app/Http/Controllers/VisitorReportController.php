<?php

namespace Modules\VisitorManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Modules\VisitorManagement\Services\VisitorReportService;
use Modules\VisitorManagement\Enums\VisitorUserPermission;
use Illuminate\Support\Facades\Gate;

class VisitorReportController extends Controller
{
    public function __construct(
        private VisitorReportService $reportService
    ) {}

    public function index()
    {
        if (!auth()->user()->can(VisitorUserPermission::ViewReport->value)) {
            abort(403);
        }

        $reportData = $this->reportService->getReportData();

        return Inertia::render('VisitorManagement/Report/Index', [
            'reportData' => $reportData,
        ]);
    }
}
