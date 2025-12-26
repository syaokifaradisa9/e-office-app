<?php

namespace Modules\Archieve\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Modules\Archieve\Services\ArchieveReportService;
use Modules\Archieve\Enums\ArchieveUserPermission;

class ReportController extends Controller
{
    public function __construct(
        private ArchieveReportService $reportService
    ) {}

    /**
     * Division report page
     */
    public function index()
    {
        $user = auth()->user();

        if (!$user->can(ArchieveUserPermission::ViewReportDivision->value)) {
            abort(403, 'Anda tidak memiliki akses ke laporan arsip divisi.');
        }

        if (!$user->division_id) {
            abort(403, 'Anda tidak terdaftar pada divisi manapun.');
        }

        $reportData = $this->reportService->getDivisionReportData($user);

        return Inertia::render('Archieve/Report/Index', [
            'reportData' => $reportData,
            'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
        ]);
    }

    /**
     * All/Global report page
     */
    public function all()
    {
        $user = auth()->user();

        if (!$user->can(ArchieveUserPermission::ViewReportAll->value)) {
            abort(403, 'Anda tidak memiliki akses ke laporan arsip keseluruhan.');
        }

        $reportData = $this->reportService->getAllReportData();

        return Inertia::render('Archieve/Report/All', [
            'reportData' => $reportData,
            'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
        ]);
    }
}
