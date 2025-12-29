<?php

namespace Modules\VisitorManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\DatatableRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\VisitorManagement\DataTransferObjects\FeedbackQuestionDTO;
use Modules\VisitorManagement\Datatables\FeedbackQuestionDataTableService;
use Modules\VisitorManagement\Models\VisitorFeedbackQuestion;
use Modules\VisitorManagement\Services\FeedbackQuestionService;
use Modules\VisitorManagement\Enums\VisitorUserPermission;

class FeedbackQuestionController extends Controller
{
    public function __construct(
        private FeedbackQuestionService $feedbackQuestionService,
        private FeedbackQuestionDataTableService $dataTableService
    ) {}

    public function index(DatatableRequest $request)
    {
        $user = auth()->user();
        
        if (!$user->hasAnyPermission([
            VisitorUserPermission::ViewFeedbackQuestion->value,
            VisitorUserPermission::ManageFeedbackQuestion->value,
        ])) {
            abort(403);
        }

        return Inertia::render('VisitorManagement/FeedbackQuestion/Index', [
            'questions' => $this->dataTableService->getDatatable($request),
            'filters' => $request->only(['search', 'status']),
            'canManage' => $user->hasPermissionTo(VisitorUserPermission::ManageFeedbackQuestion->value),
        ]);
    }

    public function datatable(DatatableRequest $request)
    {
        $user = auth()->user();
        
        if (!$user->hasAnyPermission([
            VisitorUserPermission::ViewFeedbackQuestion->value,
            VisitorUserPermission::ManageFeedbackQuestion->value,
        ])) {
            abort(403);
        }

        return response()->json($this->dataTableService->getDatatable($request));
    }

    public function create()
    {
        $user = auth()->user();
        
        if (!$user->hasPermissionTo(VisitorUserPermission::ManageFeedbackQuestion->value)) {
            abort(403);
        }

        return Inertia::render('VisitorManagement/FeedbackQuestion/Create');
    }

    public function edit(VisitorFeedbackQuestion $question)
    {
        $user = auth()->user();
        
        if (!$user->hasPermissionTo(VisitorUserPermission::ManageFeedbackQuestion->value)) {
            abort(403);
        }

        return Inertia::render('VisitorManagement/FeedbackQuestion/Create', [
            'feedbackQuestion' => $question,
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        
        if (!$user->hasPermissionTo(VisitorUserPermission::ManageFeedbackQuestion->value)) {
            abort(403);
        }

        $request->validate([
            'question' => 'required|string|max:500|unique:visitor_feedback_questions,question',
            'is_active' => 'boolean',
        ]);

        $dto = FeedbackQuestionDTO::fromRequest($request);
        $this->feedbackQuestionService->store($dto);

        return redirect()->route('visitor.feedback-questions.index')->with('success', 'Pertanyaan feedback berhasil ditambahkan.');
    }

    public function update(Request $request, VisitorFeedbackQuestion $question)
    {
        $user = auth()->user();
        
        if (!$user->hasPermissionTo(VisitorUserPermission::ManageFeedbackQuestion->value)) {
            abort(403);
        }

        $request->validate([
            'question' => 'required|string|max:500|unique:visitor_feedback_questions,question,' . $question->id,
            'is_active' => 'boolean',
        ]);

        $dto = FeedbackQuestionDTO::fromRequest($request);
        $this->feedbackQuestionService->update($question, $dto);

        return redirect()->route('visitor.feedback-questions.index')->with('success', 'Pertanyaan feedback berhasil diperbarui.');
    }

    public function destroy(VisitorFeedbackQuestion $question)
    {
        $user = auth()->user();
        
        if (!$user->hasPermissionTo(VisitorUserPermission::ManageFeedbackQuestion->value)) {
            abort(403);
        }

        if ($this->feedbackQuestionService->hasRatings($question)) {
            return back()->withErrors(['error' => 'Pertanyaan ini sedang digunakan dan tidak dapat dihapus.']);
        }

        $this->feedbackQuestionService->delete($question);

        return back()->with('success', 'Pertanyaan feedback berhasil dihapus.');
    }

    public function toggleStatus(VisitorFeedbackQuestion $question)
    {
        $user = auth()->user();
        
        if (!$user->hasPermissionTo(VisitorUserPermission::ManageFeedbackQuestion->value)) {
            abort(403);
        }

        $this->feedbackQuestionService->toggleStatus($question);

        return back()->with('success', 'Status pertanyaan berhasil diubah.');
    }
}
