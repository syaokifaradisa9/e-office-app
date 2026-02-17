<?php

namespace Modules\VisitorManagement\Services;

use Modules\VisitorManagement\Repositories\Visitor\VisitorRepository;
use Carbon\Carbon;

class VisitorDashboardService
{
    public function __construct(
        private VisitorRepository $visitorRepository
    ) {}

    /**
     * Get visitor dashboard tabs for the unified dashboard
     */
    public function getDashboardTabs(): array
    {
        $user = auth()->user();
        $tabs = [];

        // Check if user has permission to view visitor dashboard
        if ($user->can('lihat_dashboard_pengunjung')) {
            $tabs[] = $this->getVisitorOverviewTab();
        }

        return $tabs;
    }

    /**
     * Get visitor overview tab data
     */
    private function getVisitorOverviewTab(): array
    {
        $statistics = $this->getStatistics();
        $recentVisitors = $this->visitorRepository->getRecentVisitors(5);
        $pendingVisitors = $this->visitorRepository->getPendingVisitors(5);
        $monthlyTrend = $this->getMonthlyTrend();
        $purposeDistribution = $this->getPurposeDistribution();

        return [
            'id' => 'overview',
            'label' => 'Pengunjung',
            'icon' => 'users',
            'type' => 'overview',
            'data' => [
                'statistics' => $statistics,
                'recent_visitors' => $recentVisitors,
                'pending_visitors' => $pendingVisitors,
                'monthly_trend' => $monthlyTrend,
                'purpose_distribution' => $purposeDistribution,
            ],
        ];
    }

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

    public function getMonthlyTrend(): array
    {
        $last12Months = collect(range(11, 0))->map(function ($months) {
            return Carbon::today()->subMonths($months)->format('Y-m');
        });

        $data = $this->visitorRepository->getMonthlyTrendData(12);

        return $last12Months->map(function ($month) use ($data) {
            return [
                'month' => Carbon::parse($month . '-01')->format('M Y'),
                'count' => $data[$month] ?? 0,
            ];
        })->toArray();
    }

    public function getWeeklyTrend(): array
    {
        $last7Days = collect(range(6, 0))->map(function ($days) {
            return Carbon::today()->subDays($days)->format('Y-m-d');
        });

        $data = $this->visitorRepository->getWeeklyTrendData(7);

        return $last7Days->map(function ($date) use ($data) {
            return [
                'day' => Carbon::parse($date)->format('D'),
                'count' => $data[$date] ?? 0,
            ];
        })->toArray();
    }
}
