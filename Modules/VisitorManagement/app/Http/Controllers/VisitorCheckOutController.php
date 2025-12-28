<?php

namespace Modules\VisitorManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Repositories\Division\DivisionRepository;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\VisitorManagement\DataTransferObjects\FeedbackDTO;
use Modules\VisitorManagement\Models\Visitor;
use Modules\VisitorManagement\Services\FeedbackQuestionService;
use Modules\VisitorManagement\Services\VisitorService;
use Modules\VisitorManagement\Enums\VisitorStatus;

class VisitorCheckOutController extends Controller
{
    public function __construct(
        private VisitorService $visitorService,
        private FeedbackQuestionService $feedbackQuestionService,
        private DivisionRepository $divisionRepository
    ) {}

    public function index()
    {
        return redirect()->route('visitor.check-in.list');
    }

    public function search(Request $request)
    {
        $query = $request->get('query');
        
        if (empty($query)) return response()->json([]);

        $visitors = $this->visitorService->searchCheckOut($query);

        return response()->json($visitors);
    }

    public function show(Visitor $visitor)
    {
        if (!in_array($visitor->status, [VisitorStatus::Approved, VisitorStatus::Pending, VisitorStatus::Invited])) {
            return redirect()->route('visitor.check-out.index');
        }

        return Inertia::render('VisitorManagement/CheckOut/Create', [
            'visitor' => $this->visitorService->findVisitor($visitor->id, ['division', 'purpose']),
            'questions' => $this->feedbackQuestionService->getActiveFeedbackQuestions(),
        ]);
    }

    public function store(Request $request, Visitor $visitor)
    {
        if (!in_array($visitor->status, [VisitorStatus::Approved, VisitorStatus::Pending, VisitorStatus::Invited])) {
            return back()->withErrors(['error' => 'Checkout tidak dapat dilakukan.']);
        }

        // If feedback provided, use VisitorService to submit it
        if ($request->has('ratings') && !empty($request->input('ratings'))) {
            $dto = new FeedbackDTO(
                feedback_note: $request->input('feedback_note'),
                ratings: $request->input('ratings', [])
            );
            $this->visitorService->submitFeedback($visitor, $dto);
        } else {
            // Just normal checkout without feedback
            $this->visitorService->checkOut($visitor);
        }

        return redirect()->route('visitor.check-out.success', $visitor->id);
    }

    public function cancel(Visitor $visitor)
    {
        if ($visitor->status !== VisitorStatus::Pending) {
            return back()->withErrors(['error' => 'Kunjungan tidak dapat dibatalkan.']);
        }

        $visitor->update([
            'status' => 'rejected',
            'admin_note' => 'Dibatalkan oleh pengunjung',
        ]);

        return redirect()->route('visitor.check-out.index')
            ->with('success', 'Kunjungan berhasil dibatalkan.');
    }

    public function success(Visitor $visitor)
    {
        return Inertia::render('VisitorManagement/CheckOut/Success', [
            'visitor' => $this->visitorService->findVisitor($visitor->id, ['division', 'purpose']),
        ]);
    }
}
