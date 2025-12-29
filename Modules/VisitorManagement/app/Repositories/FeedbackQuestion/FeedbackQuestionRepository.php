<?php

namespace Modules\VisitorManagement\Repositories\FeedbackQuestion;

use Modules\VisitorManagement\Models\VisitorFeedbackQuestion;
use Illuminate\Database\Eloquent\Collection;

interface FeedbackQuestionRepository
{
    public function all(): Collection;
    public function findById(int $id): ?VisitorFeedbackQuestion;
    public function create(array $data): VisitorFeedbackQuestion;
    public function update(VisitorFeedbackQuestion $question, array $data): bool;
    public function delete(VisitorFeedbackQuestion $question): bool;
    public function hasRatings(VisitorFeedbackQuestion $question): bool;
    public function getDatatableQuery(array $params): \Illuminate\Database\Eloquent\Builder;
}
