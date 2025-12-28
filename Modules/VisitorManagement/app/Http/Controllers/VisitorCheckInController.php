<?php

namespace Modules\VisitorManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Repositories\Division\DivisionRepository;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\VisitorManagement\DataTransferObjects\CheckInDTO;
use Modules\VisitorManagement\Http\Requests\CheckInRequest;
use Modules\VisitorManagement\Models\Visitor;
use Modules\VisitorManagement\Services\PurposeService;
use Modules\VisitorManagement\Services\VisitorService;

class VisitorCheckInController extends Controller
{
    public function __construct(
        private VisitorService $visitorService,
        private PurposeService $purposeService,
        private DivisionRepository $divisionRepository
    ) {}

    public function index(Request $request)
    {
        $visitor = null;
        if ($request->has('visitor_id')) {
            $visitor = $this->visitorService->findVisitor($request->get('visitor_id'), ['division', 'purpose']);
        }

        return Inertia::render('VisitorManagement/CheckIn/Create', [
            'divisions' => $this->divisionRepository->all(['id', 'name']),
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
        if ($visitor->status !== 'pending') {
            return redirect()->route('visitor.check-in.list')
                ->withErrors(['error' => 'Hanya data kunjungan pending yang dapat diedit.']);
        }

        return Inertia::render('VisitorManagement/CheckIn/Create', [
            'divisions' => $this->divisionRepository->all(['id', 'name']),
            'purposes' => $this->purposeService->getActivePurposes(),
            'visitor' => $this->visitorService->findVisitor($visitor->id, ['division', 'purpose']),
            'isEdit' => true,
        ]);
    }

    public function update(CheckInRequest $request, Visitor $visitor)
    {
        if ($visitor->status !== 'pending') {
            return back()->withErrors(['error' => 'Hanya data kunjungan pending yang dapat diedit.']);
        }

        $dto = CheckInDTO::fromRequest($request);
        $this->visitorService->updateVisitor($visitor, $dto);

        return redirect()->route('visitor.check-in.success', $visitor->id);
    }
}
