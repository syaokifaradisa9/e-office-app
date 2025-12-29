<?php

namespace Modules\VisitorManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\DivisionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\VisitorManagement\DataTransferObjects\CheckInDTO;
use Modules\VisitorManagement\Http\Requests\CheckInRequest;
use Modules\VisitorManagement\Models\Visitor;
use Modules\VisitorManagement\Services\PurposeService;
use Modules\VisitorManagement\Services\VisitorService;
use Modules\VisitorManagement\Enums\VisitorStatus;

class VisitorCheckInController extends Controller
{
    public function __construct(
        private VisitorService $visitorService,
        private PurposeService $purposeService,
        private DivisionService $divisionService
    ) {}

    public function index(Request $request)
    {
        $visitor = null;
        if ($request->has('visitor_id')) {
            $visitor = $this->visitorService->findVisitor($request->get('visitor_id'), ['division', 'purpose']);
        }

        return Inertia::render('VisitorManagement/CheckIn/Create', [
            'divisions' => $this->divisionService->getAll(),
            'purposes' => $this->purposeService->getActivePurposes(),
            'visitor' => $visitor,
        ]);
    }

    public function store(CheckInRequest $request)
    {
        $dto = CheckInDTO::fromRequest($request);
        $visitor = $this->visitorService->registerVisitor($dto);

        return redirect()->route('visitor.check-in.success', $visitor->id);
    }

    public function success(Visitor $visitor)
    {
        return Inertia::render('VisitorManagement/CheckIn/Success', [
            'visitor' => $this->visitorService->findVisitor($visitor->id, ['division', 'purpose']),
        ]);
    }

    public function list()
    {
        return Inertia::render('VisitorManagement/CheckIn/List', [
            'visitors' => $this->visitorService->getPublicList(),
        ]);
    }

    public function edit(Visitor $visitor)
    {
        // Allow both pending and invited visitors to access this page
        if (!in_array($visitor->status, [VisitorStatus::Pending, VisitorStatus::Invited])) {
            return redirect()->route('visitor.check-in.list')
                ->withErrors(['error' => 'Hanya data kunjungan pending atau invited yang dapat diedit.']);
        }

        $isInvited = $visitor->status === VisitorStatus::Invited;

        return Inertia::render('VisitorManagement/CheckIn/Create', [
            'divisions' => $this->divisionService->getAll(),
            'purposes' => $this->purposeService->getActivePurposes(),
            'visitor' => $this->visitorService->findVisitor($visitor->id, ['division', 'purpose']),
            'isEdit' => !$isInvited, // Edit mode for pending, Check-in mode for invited
            'isInvited' => $isInvited,
        ]);
    }

    public function update(CheckInRequest $request, Visitor $visitor)
    {
        // Allow both pending and invited visitors
        if (!in_array($visitor->status, [VisitorStatus::Pending, VisitorStatus::Invited])) {
            return back()->withErrors(['error' => 'Hanya data kunjungan pending atau invited yang dapat diproses.']);
        }

        $dto = CheckInDTO::fromRequest($request);
        $this->visitorService->updateVisitor($visitor, $dto);

        return redirect()->route('visitor.check-in.success', $visitor->id);
    }
}
