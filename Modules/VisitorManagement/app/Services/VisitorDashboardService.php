<?php

namespace Modules\VisitorManagement\Services;

use Modules\VisitorManagement\Repositories\Visitor\VisitorRepository;
use Carbon\Carbon;

class VisitorDashboardService
{
    public function __construct(
        private VisitorRepository $visitorRepository
    ) {}

    public function getStatistics(): array
    {
        return [
            'today_visitors' => $this->visitorRepository->getTodayCount(),
            'active_visitors' => $this->visitorRepository->getActiveCount(),
            'rejected_visits' => $this->visitorRepository->getTodayRejectedCount(),
            'average_rating' => round($this->visitorRepository->getAverageRating(), 1),
        ];
    }

    public function getPurposeDistribution(): array
    {
        return $this->visitorRepository->getPurposeDistribution();
    }

    public function getWeeklyTrend(): array
    {
        $last7Days = collect(range(6, 0))->map(function ($days) {
            return Carbon::today()->subDays($days)->format('Y-m-d');
        });

        $data = $this->visitorRepository->getWeeklyTrendData(7);

        return $last7Days->map(function ($date) use ($data) {
            return [
                'date' => Carbon::parse($date)->format('d M'),
                'count' => $data[$date] ?? 0,
            ];
        })->toArray();
    }
}
