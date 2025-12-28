<?php

namespace Modules\VisitorManagement\Repositories\FeedbackQuestion;

use Modules\VisitorManagement\Models\VisitorFeedbackQuestion;
use Illuminate\Database\Eloquent\Collection;

class EloquentFeedbackQuestionRepository implements FeedbackQuestionRepository
{
    public function all(): Collection
    {
        return VisitorFeedbackQuestion::all();
    }

    public function findById(int $id): ?VisitorFeedbackQuestion
    {
        return VisitorFeedbackQuestion::find($id);
    }

    public function create(array $data): VisitorFeedbackQuestion
    {
        return VisitorFeedbackQuestion::create($data);
    }

    public function update(VisitorFeedbackQuestion $question, array $data): bool
    {
        return $question->update($data);
    }

    public function delete(VisitorFeedbackQuestion $question): bool
    {
        return $question->delete();
    }

    public function hasRatings(VisitorFeedbackQuestion $question): bool
    {
        return $question->ratings()->exists();
    }
}
