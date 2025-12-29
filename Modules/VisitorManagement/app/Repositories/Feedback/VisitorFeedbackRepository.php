<?php

namespace Modules\VisitorManagement\Repositories\Feedback;

interface VisitorFeedbackRepository
{
    public function datatable();
    public function find(int $id);
    public function markAsRead(int $id): bool;
    public function getAverageRating(): float;
    public function getRatingDistribution(): array;
    public function getQuestionStats(): array;
    public function getTotalFeedbacksCount(): int;
    public function saveFeedback(\Modules\VisitorManagement\Models\Visitor $visitor, array $data, array $ratings): bool;
}
