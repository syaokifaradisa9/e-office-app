<?php

namespace Modules\Ticketing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Ticketing\Services\TicketingReportService;
use Modules\Ticketing\Enums\TicketingPermission;

class TicketingReportController extends Controller
{
    public function __construct(
        private TicketingReportService $reportService
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->can(TicketingPermission::ViewAllReport->value)) {
            return redirect()->route('ticketing.reports.all');
        }

        if ($user->can(TicketingPermission::ViewDivisionReport->value)) {
            return redirect()->route('ticketing.reports.division');
        }

        abort(403);
    }

    public function division(Request $request)
    {
        $user = $request->user();
        abort_unless($user->can(TicketingPermission::ViewDivisionReport->value), 403);

        $data = $this->reportService->getDivisionReportData($user);

        return Inertia::render('Ticketing/Report', [
            'reportData' => $data,
            'type' => 'division'
        ]);
    }

    public function all(Request $request)
    {
        abort_unless($request->user()->can(TicketingPermission::ViewAllReport->value), 403);

        $divisionId = $request->input('division_id');
        $data = $this->reportService->getAllReportData($divisionId);
        $divisions = \App\Models\Division::all();

        return Inertia::render('Ticketing/Report', [
            'reportData' => $data,
            'type' => 'all',
            'divisions' => $divisions,
            'currentDivisionId' => $divisionId
        ]);
    }
}
