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
    public function getMonthlyTrendData(int $months = 12): array;
    public function getRecentVisitors(int $limit = 5): array;
    public function getPendingVisitors(int $limit = 5): array;
    public function getDatatableQuery(array $params): \Illuminate\Database\Eloquent\Builder;
    public function countByStatus(string $status, \Carbon\Carbon $startDate = null): int;
    public function countByDate(\Carbon\Carbon $startDate): int;
    public function getDetailedMonthlyTrend(int $year): array;
    public function getStatusDistribution(int $year): array;
    public function getPeakHours(int $year): array;
    public function getBusiestDays(int $year): array;
    public function getTopOrganizations(int $year, int $limit = 10): array;
    public function getAverageDuration(int $year): int;
    public function getRepeatVisitors(int $year, int $limit = 10): array;
    public function getActiveVisitorsCount(): int;
    public function getPurposeRankings(int $year, int $limit = 10, string $direction = 'desc'): array;
    public function getDivisionRankings(int $year, int $limit = 10, string $direction = 'desc'): array;
}
