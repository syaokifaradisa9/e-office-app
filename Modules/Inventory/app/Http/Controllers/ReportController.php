<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Inventory\Services\LookupService;
use Modules\Inventory\Services\ReportService;

class ReportController extends Controller
{
    public function __construct(
        private ReportService $reportService,
        private LookupService $lookupService
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->can('lihat_laporan_gudang_semua')) {
            return redirect()->route('inventory.reports.all');
        }

        return redirect()->route('inventory.reports.division');
    }

    public function division(Request $request)
    {
        $user = $request->user();
        $data = $this->reportService->getDivisionReportData($user);

        return Inertia::render('Inventory/Report/Index', [
            'reportData' => $data,
            'divisions' => $this->lookupService->getActiveDivisions(),
            'type' => 'division'
        ]);
    }

    public function all(Request $request)
    {
        $data = $this->reportService->getAllReportData();

        return Inertia::render('Inventory/Report/All', [
            'reportData' => $data,
            'divisions' => $this->lookupService->getActiveDivisions(),
            'type' => 'all'
        ]);
    }

    public function printExcel(Request $request)
    {
        return $this->reportService->printExcel($request->user());
    }
}
