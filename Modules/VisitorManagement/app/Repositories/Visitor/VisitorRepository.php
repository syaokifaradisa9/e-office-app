<?php

namespace Modules\VisitorManagement\Repositories\Visitor;

use Modules\VisitorManagement\Models\Visitor;
use Illuminate\Database\Eloquent\Collection;

interface VisitorRepository
{
    public function findById(int $id): ?Visitor;
    public function findByIdWith(int $id, array $relations): ?Visitor;
    public function create(array $data): Visitor;
    public function update(Visitor $visitor, array $data): bool;
    public function delete(Visitor $visitor): bool;
    public function getActiveVisitors(): Collection;
    public function getCheckOutList(int $limit = 50): Collection;
    public function searchCheckOut(string $query, int $limit = 10): Collection;
    public function getPublicList(): Collection;
    public function getTodayCount(): int;
    public function getActiveCount(): int;
    public function getTodayRejectedCount(): int;
    public function getAverageRating(): float;
    public function getPurposeDistribution(): array;
    public function getWeeklyTrendData(int $days = 7): array;
}
