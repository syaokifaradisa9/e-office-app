<?php

namespace Modules\VisitorManagement\Repositories\Feedback;

use Modules\VisitorManagement\Models\VisitorFeedback;

class EloquentVisitorFeedbackRepository implements VisitorFeedbackRepository
{
    public function datatable()
    {
        return VisitorFeedback::with(['visitor', 'ratings'])
            ->whereNotNull('feedback_note')
            ->where('feedback_note', '!=', '');
    }

    public function find(int $id)
    {
        return VisitorFeedback::findOrFail($id);
    }

    public function markAsRead(int $id): bool
    {
        $feedback = $this->find($id);
        return $feedback->update(['is_read' => true]);
    }

    public function getAverageRating(): float
    {
        return (float) (\Modules\VisitorManagement\Models\VisitorFeedbackRating::avg('rating') ?? 0);
    }

    public function getRatingDistribution(): array
    {
        return \Modules\VisitorManagement\Models\VisitorFeedbackRating::select('rating', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();
    }

    public function getQuestionStats(): array
    {
        return \Illuminate\Support\Facades\DB::table('visitor_feedback_ratings')
            ->join('visitor_feedback_questions', 'visitor_feedback_ratings.question_id', '=', 'visitor_feedback_questions.id')
            ->select('visitor_feedback_questions.question', \Illuminate\Support\Facades\DB::raw('AVG(rating) as avg_rating'), \Illuminate\Support\Facades\DB::raw('COUNT(*) as total'))
            ->groupBy('visitor_feedback_questions.id', 'visitor_feedback_questions.question')
            ->orderByDesc('avg_rating')
            ->get()
            ->toArray();
    }

    public function getTotalFeedbacksCount(): int
    {
        return \Modules\VisitorManagement\Models\VisitorFeedbackRating::distinct('visitor_feedback_id')->count('visitor_feedback_id');
    }

    public function saveFeedback(\Modules\VisitorManagement\Models\Visitor $visitor, array $data, array $ratings): bool
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($visitor, $data, $ratings) {
            $feedback = $visitor->feedback()->create($data);

            foreach ($ratings as $questionId => $rating) {
                $feedback->ratings()->create([
                    'question_id' => $questionId,
                    'rating' => (int) $rating,
                ]);
            }

            return true;
        });
    }

    public function delete(int $id): bool
    {
        $feedback = $this->find($id);
        return $feedback->delete();
    }
}
