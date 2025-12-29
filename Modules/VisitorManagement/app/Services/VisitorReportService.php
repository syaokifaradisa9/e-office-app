<?php

namespace Modules\VisitorManagement\Services;

use Modules\VisitorManagement\Repositories\Visitor\VisitorRepository;
use Modules\VisitorManagement\Repositories\Feedback\VisitorFeedbackRepository;
use Carbon\Carbon;

class VisitorReportService
{
    public function __construct(
        private VisitorRepository $visitorRepository,
        private VisitorFeedbackRepository $feedbackRepository
    ) {}

    public function getReportData(): array
    {
        return [
            'overview_stats' => $this->getOverviewStats(),
            'monthly_trend' => $this->getMonthlyTrend(),
            'status_distribution' => $this->getStatusDistribution(),
            'purpose_rankings' => $this->getPurposeRankings(),
            'division_rankings' => $this->getDivisionRankings(),
            'feedback_stats' => $this->getFeedbackStats(),
            'peak_hours' => $this->getPeakHours(),
            'busiest_days' => $this->getBusiestDays(),
            'top_organizations' => $this->getTopOrganizations(),
            'average_duration' => $this->getAverageVisitDuration(),
            'month_comparison' => $this->getMonthComparison(),
            'repeat_visitors' => $this->getRepeatVisitors(),
            'active_visitors' => $this->getActiveVisitors(),
        ];
    }

    private function getOverviewStats(): array
    {
        $now = Carbon::now();
        $startOfYear = $now->copy()->startOfYear();
        $startOfMonth = $now->copy()->startOfMonth();
        $today = $now->copy()->startOfDay();

        return [
            'total_visitors_year' => $this->visitorRepository->countByDate($startOfYear),
            'total_visitors_month' => $this->visitorRepository->countByDate($startOfMonth),
            'total_visitors_today' => $this->visitorRepository->countByDate($today),
            'approved_count' => $this->visitorRepository->countByStatus('approved', $startOfYear),
            'rejected_count' => $this->visitorRepository->countByStatus('rejected', $startOfYear),
            'completed_count' => $this->visitorRepository->countByStatus('completed', $startOfYear),
            'pending_count' => $this->visitorRepository->countByStatus('pending'),
            'average_rating' => round($this->feedbackRepository->getAverageRating(), 1),
        ];
    }

    private function getMonthlyTrend(): array
    {
        return $this->visitorRepository->getDetailedMonthlyTrend(Carbon::now()->year);
    }

    private function getStatusDistribution(): array
    {
        return $this->visitorRepository->getStatusDistribution(Carbon::now()->year);
    }

    private function getPurposeRankings(): array
    {
        $year = Carbon::now()->year;

        return [
            'most_visited' => $this->visitorRepository->getPurposeRankings($year, 10, 'desc'),
            'least_visited' => $this->visitorRepository->getPurposeRankings($year, 10, 'asc'),
        ];
    }

    private function getDivisionRankings(): array
    {
        $year = Carbon::now()->year;

        return [
            'most_visited' => $this->visitorRepository->getDivisionRankings($year, 10, 'desc'),
            'least_visited' => $this->visitorRepository->getDivisionRankings($year, 10, 'asc'),
        ];
    }

    private function getFeedbackStats(): array
    {
        return [
            'rating_distribution' => $this->feedbackRepository->getRatingDistribution(),
            'question_stats' => $this->feedbackRepository->getQuestionStats(),
            'average_rating' => round($this->feedbackRepository->getAverageRating(), 1),
            'total_feedbacks' => $this->feedbackRepository->getTotalFeedbacksCount(),
        ];
    }

    private function getPeakHours(): array
    {
        return $this->visitorRepository->getPeakHours(Carbon::now()->year);
    }

    public function exportExcel()
    {
        $data = $this->getReportData();

        return response()->streamDownload(function () use ($data) {
            $writer = new \OpenSpout\Writer\XLSX\Writer();
            $writer->openToFile('php://output');

            // 1. Overview Section
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['RINGKASAN LAPORAN TAHUN ' . Carbon::now()->year]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['']));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Statistik Utama']));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
                'Total Pengunjung Tahun Ini',
                'Total Pengunjung Bulan Ini',
                'Total Pengunjung Hari Ini',
                'Rata-rata Rating',
                'Total Feedback'
            ]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
                $data['overview_stats']['total_visitors_year'],
                $data['overview_stats']['total_visitors_month'],
                $data['overview_stats']['total_visitors_today'],
                $data['overview_stats']['average_rating'],
                $data['feedback_stats']['total_feedbacks']
            ]));

            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['']));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Status Kunjungan']));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
                'Disetujui',
                'Ditolak',
                'Selesai',
                'Pending'
            ]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
                $data['overview_stats']['approved_count'],
                $data['overview_stats']['rejected_count'],
                $data['overview_stats']['completed_count'],
                $data['overview_stats']['pending_count']
            ]));

            // 2. Monthly Trend
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['']));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['TREN BULANAN']));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Bulan', 'Total Pengunjung', 'Selesai', 'Ditolak']));
            foreach ($data['monthly_trend'] as $trend) {
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
                    $trend['month'],
                    $trend['total_visitors'],
                    $trend['completed'],
                    $trend['rejected']
                ]));
            }

            // 3. Purpose Rankings
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['']));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['PERINGKAT KEPERLUAN KUNJUNGAN']));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Keperluan', 'Total Kunjungan']));
            foreach ($data['purpose_rankings']['most_visited'] as $purpose) {
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([$purpose->name, $purpose->total]));
            }

            // 4. Division Rankings
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['']));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['PERINGKAT DIVISI TUJUAN']));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Divisi', 'Total Kunjungan']));
            foreach ($data['division_rankings']['most_visited'] as $division) {
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([$division->name, $division->total]));
            }

            // 5. Top Organizations
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['']));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['TOP ORGANISASI / INSTANSI']));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Organisasi', 'Total Kunjungan']));
            foreach ($data['top_organizations'] as $org) {
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([$org['organization'], $org['total']]));
            }

            $writer->close();
        }, 'Laporan_Statistik_Pengunjung_' . date('Ymd_His') . '.xlsx');
    }

    private function getBusiestDays(): array
    {
        $year = Carbon::now()->year;
        $dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

        $data = $this->visitorRepository->getBusiestDays($year);

        $result = [];
        for ($i = 1; $i <= 7; $i++) {
            $result[] = [
                'day' => $dayNames[$i - 1],
                'count' => $data[$i] ?? 0,
            ];
        }

        return $result;
    }

    private function getTopOrganizations(): array
    {
        return $this->visitorRepository->getTopOrganizations(Carbon::now()->year);
    }

    private function getAverageVisitDuration(): array
    {
        $avgMinutes = $this->visitorRepository->getAverageDuration(Carbon::now()->year);

        $hours = floor($avgMinutes / 60);
        $minutes = $avgMinutes % 60;

        return [
            'minutes' => $avgMinutes,
            'formatted' => $hours > 0 ? "{$hours} jam {$minutes} menit" : "{$minutes} menit",
        ];
    }

    private function getMonthComparison(): array
    {
        $now = Carbon::now();
        $thisMonthStart = $now->copy()->startOfMonth();
        $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

        $thisMonth = $this->visitorRepository->countByDate($thisMonthStart);
        
        // Simulating last month count by subtracting this month from count since last month start
        // This is a bit hacky but works for now without adding countBetween to Repo
        $lastMonth = $this->visitorRepository->countByDate($lastMonthStart) - $thisMonth;

        $change = 0;
        if ($lastMonth > 0) {
            $change = round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1);
        } elseif ($thisMonth > 0) {
            $change = 100;
        }

        return [
            'this_month' => $thisMonth,
            'last_month' => $lastMonth,
            'change_percent' => $change,
            'trend' => $change >= 0 ? 'up' : 'down',
        ];
    }

    private function getRepeatVisitors(): array
    {
        return $this->visitorRepository->getRepeatVisitors(Carbon::now()->year);
    }

    private function getActiveVisitors(): int
    {
        return $this->visitorRepository->getActiveVisitorsCount();
    }
}
