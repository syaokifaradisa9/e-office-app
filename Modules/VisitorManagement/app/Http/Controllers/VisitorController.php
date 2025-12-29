<?php

namespace Modules\VisitorManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\DatatableRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\VisitorManagement\DataTransferObjects\ConfirmVisitDTO;
use Modules\VisitorManagement\Datatables\VisitorDataTableService;
use Modules\VisitorManagement\Http\Requests\ConfirmVisitRequest;
use Modules\VisitorManagement\Http\Requests\CreateInvitationRequest;
use Modules\VisitorManagement\Models\Visitor;
use Modules\VisitorManagement\Services\VisitorService;
use Modules\VisitorManagement\Services\PurposeService;
use App\Services\DivisionService;

class VisitorController extends Controller
{
    public function __construct(
        private VisitorService $visitorService,
        private VisitorDataTableService $dataTableService,
        private PurposeService $purposeService,
        private DivisionService $divisionService
    ) {}

    public function index(DatatableRequest $request)
    {
        return Inertia::render('VisitorManagement/Visitor/Index', [
            'initialVisitors' => $this->dataTableService->getDatatable($request, auth()->user()),
        ]);
    }

    public function create()
    {
        return Inertia::render('VisitorManagement/Visitor/Create', [
            'divisions' => $this->divisionService->getAll(),
            'purposes' => $this->purposeService->getActivePurposes(),
        ]);
    }

    public function datatable(DatatableRequest $request)
    {
        return response()->json($this->dataTableService->getDatatable($request, auth()->user()));
    }

    public function confirm(ConfirmVisitRequest $request, Visitor $visitor)
    {
        $dto = ConfirmVisitDTO::fromRequest($request, auth()->id());
        $this->visitorService->confirmVisit($visitor, $dto);

        return redirect()->back()->with('success', 'Kunjungan berhasil dikonfirmasi.');
    }

    public function export(DatatableRequest $request)
    {
        return $this->dataTableService->printExcel($request, auth()->user());
    }

    public function show(Visitor $visitor)
    {
        return Inertia::render('VisitorManagement/Visitor/Detail', [
            'visitor' => $this->visitorService->findVisitor($visitor->id, ['division', 'purpose', 'confirmedBy', 'feedback.ratings.question']),
        ]);
    }

    public function storeInvitation(CreateInvitationRequest $request)
    {
        $data = $request->validated();
        $data['status'] = 'invited';
        
        $this->visitorService->registerVisitorFromData($data);

        return redirect()->route('visitor.index')->with('success', 'Undangan tamu berhasil dibuat.');
    }
}
