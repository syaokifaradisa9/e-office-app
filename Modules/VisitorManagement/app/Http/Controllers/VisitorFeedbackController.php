<?php

namespace Modules\VisitorManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\VisitorManagement\Services\VisitorFeedbackManagementService;
use Modules\VisitorManagement\Enums\VisitorUserPermission;

class VisitorFeedbackController extends Controller
{
    public function __construct(
        protected VisitorFeedbackManagementService $service
    ) {}

    public function index()
    {
        if (!auth()->user()->can(VisitorUserPermission::ViewCriticismFeedback->value)) {
            abort(403);
        }

        return Inertia::render('VisitorManagement/Feedback/Index');
    }

    public function datatable(Request $request)
    {
        if (!auth()->user()->can(VisitorUserPermission::ViewCriticismFeedback->value)) {
            abort(403);
        }

        return $this->service->datatable($request);
    }

    public function markAsRead($id)
    {
        if (!auth()->user()->can(VisitorUserPermission::ManageCriticismFeedback->value)) {
            abort(403);
        }

        $this->service->markAsRead($id);

        return back()->with('success', 'Berhasil menandai sebagai dibaca');
    }

    public function export()
    {
        if (!auth()->user()->can(VisitorUserPermission::ViewCriticismFeedback->value)) {
            abort(403);
        }

        return $this->service->exportExcel();
    }

    public function destroy($id)
    {
        if (!auth()->user()->can(VisitorUserPermission::ManageCriticismFeedback->value)) {
            abort(403);
        }

        $this->service->delete($id);

        return back()->with('success', 'Berhasil menghapus kritik dan saran');
    }

    public function show($id)
    {
        if (!auth()->user()->can(VisitorUserPermission::ViewCriticismFeedback->value)) {
            abort(403);
        }

        $feedback = $this->service->findById($id);

        return Inertia::render('VisitorManagement/Feedback/FeedbackDetail', [
            'feedback' => $feedback
        ]);
    }
}
