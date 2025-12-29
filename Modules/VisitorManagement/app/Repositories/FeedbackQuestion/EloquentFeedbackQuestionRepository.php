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

    public function getDatatableQuery(array $params): \Illuminate\Database\Eloquent\Builder
    {
        $query = VisitorFeedbackQuestion::query();

        if (isset($params['search']) && !empty($params['search'])) {
            $search = $params['search'];
            $query->where('question', 'like', "%{$search}%");
        }

        if (isset($params['question']) && !empty($params['question'])) {
            $query->where('question', 'like', '%' . $params['question'] . '%');
        }

        if (isset($params['status']) && !empty($params['status'])) {
            $query->where('is_active', $params['status'] === 'active');
        }

        if (isset($params['sort_by']) && isset($params['sort_direction'])) {
            $query->orderBy($params['sort_by'], $params['sort_direction']);
        } else {
            $query->orderBy('id', 'desc');
        }

        return $query;
    }
}
