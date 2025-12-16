<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Division;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Inventory\Services\ReportService;

class ReportController extends Controller
{
    public function __construct(
        private ReportService $reportService
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();

        $data = $this->reportService->getReportData($user);

        return Inertia::render('Inventory/Report/Index', [
            'reportData' => $data,
            'divisions' => Division::all(),
        ]);
    }

    public function printExcel(Request $request)
    {
        return $this->reportService->printExcel($request->user());
    }
}
