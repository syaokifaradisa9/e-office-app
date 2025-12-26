<?php

namespace Modules\Archieve\Services;

use App\Models\Division;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Archieve\Models\Category;
use Modules\Archieve\Models\Document;
use Modules\Archieve\Models\DocumentClassification;
use Modules\Archieve\Models\DivisionStorage;

class ArchieveReportService
{
    /**
     * Get division report data
     */
    public function getDivisionReportData(User $user): array
    {
        $divisionId = $user->division_id;

        return [
            'division_name' => $user->division?->name,
            'overview_stats' => $this->getOverviewStats($divisionId),
            'upload_trend' => $this->getMonthlyUploadTrend($divisionId),
            'category_rankings' => [
                'most_documents' => $this->getCategoryRankings($divisionId, 'most', 10),
                'least_documents' => $this->getCategoryRankings($divisionId, 'least', 10),
            ],
            'classification_rankings' => [
                'most_documents' => $this->getClassificationRankings($divisionId, 10),
            ],
            'storage_trend' => $this->getStorageTrend($divisionId),
            'file_type_distribution' => $this->getFileTypeDistribution($divisionId),
            'stagnant_documents' => $this->getStagnantDocuments($divisionId, 6),
            'top_uploaders' => $this->getTopUploaders($divisionId, 10),
            'largest_documents' => $this->getLargestDocuments($divisionId, 10),
        ];
    }

    /**
     * Get all/global report data
     */
    public function getAllReportData(): array
    {
        $divisions = Division::all();

        return [
            'global' => [
                'overview_stats' => $this->getOverviewStats(null),
                'upload_trend' => $this->getMonthlyUploadTrend(null),
                'category_rankings' => [
                    'most_documents' => $this->getCategoryRankings(null, 'most', 10),
                    'least_documents' => $this->getCategoryRankings(null, 'least', 10),
                ],
                'classification_rankings' => [
                    'most_documents' => $this->getClassificationRankings(null, 10),
                ],
                'file_type_distribution' => $this->getFileTypeDistribution(null),
                'stagnant_documents' => $this->getStagnantDocuments(null, 6),
                'top_uploaders' => $this->getTopUploaders(null, 10),
                'largest_documents' => $this->getLargestDocuments(null, 10),
                'division_comparison' => $this->getDivisionComparison(),
            ],
            'per_division' => $divisions->map(function ($division) {
                return [
                    'division_id' => $division->id,
                    'division_name' => $division->name,
                    'overview_stats' => $this->getOverviewStats($division->id),
                    'upload_trend' => $this->getMonthlyUploadTrend($division->id),
                    'category_rankings' => [
                        'most_documents' => $this->getCategoryRankings($division->id, 'most', 5),
                    ],
                    'file_type_distribution' => $this->getFileTypeDistribution($division->id),
                ];
            })->toArray(),
        ];
    }

    /**
     * Get overview statistics
     */
    private function getOverviewStats(?int $divisionId): array
    {
        $query = Document::query();
        
        if ($divisionId) {
            $query->whereHas('divisions', fn ($q) => $q->where('division_id', $divisionId));
        }

        $totalDocuments = (clone $query)->count();
        $totalSize = (clone $query)->sum('file_size');
        $thisMonthDocuments = (clone $query)->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $lastMonthDocuments = (clone $query)->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();

        $growthRate = $lastMonthDocuments > 0 
            ? round((($thisMonthDocuments - $lastMonthDocuments) / $lastMonthDocuments) * 100, 1)
            : 0;

        $storage = null;
        if ($divisionId) {
            $storage = DivisionStorage::where('division_id', $divisionId)->first();
        }

        return [
            'total_documents' => $totalDocuments,
            'total_size' => $totalSize,
            'total_size_label' => $this->formatBytes($totalSize),
            'this_month_documents' => $thisMonthDocuments,
            'last_month_documents' => $lastMonthDocuments,
            'growth_rate' => $growthRate,
            'storage_used' => $totalSize,
            'storage_max' => $storage?->max_size ?? 0,
            'storage_percentage' => $storage && $storage->max_size > 0 
                ? round(($totalSize / $storage->max_size) * 100, 1) 
                : 0,
        ];
    }

    /**
     * Get monthly upload trend
     */
    private function getMonthlyUploadTrend(?int $divisionId): array
    {
        $query = Document::select(
            DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
            DB::raw('COUNT(*) as total_documents'),
            DB::raw('SUM(file_size) as total_size')
        );

        if ($divisionId) {
            $query->whereHas('divisions', fn ($q) => $q->where('division_id', $divisionId));
        }

        return $query->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->toArray();
    }

    /**
     * Get category rankings
     */
    private function getCategoryRankings(?int $divisionId, string $type = 'most', int $limit = 10): array
    {
        $query = Category::withCount(['documents' => function ($q) use ($divisionId) {
            if ($divisionId) {
                $q->whereHas('divisions', fn ($dq) => $dq->where('division_id', $divisionId));
            }
        }]);

        if ($type === 'most') {
            $query->orderByDesc('documents_count');
        } else {
            $query->orderBy('documents_count');
        }

        return $query->limit($limit)
            ->get()
            ->map(fn ($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'count' => $cat->documents_count,
            ])
            ->toArray();
    }

    /**
     * Get classification rankings
     */
    private function getClassificationRankings(?int $divisionId, int $limit = 10): array
    {
        $query = DocumentClassification::withCount(['documents' => function ($q) use ($divisionId) {
            if ($divisionId) {
                $q->whereHas('divisions', fn ($dq) => $dq->where('division_id', $divisionId));
            }
        }]);

        return $query->orderByDesc('documents_count')
            ->limit($limit)
            ->get()
            ->map(fn ($cls) => [
                'id' => $cls->id,
                'code' => $cls->code,
                'name' => $cls->name,
                'count' => $cls->documents_count,
            ])
            ->toArray();
    }

    /**
     * Get storage trend over time
     */
    private function getStorageTrend(?int $divisionId): array
    {
        $query = Document::select(
            DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
            DB::raw('SUM(file_size) as cumulative_size')
        );

        if ($divisionId) {
            $query->whereHas('divisions', fn ($q) => $q->where('division_id', $divisionId));
        }

        $monthly = $query->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Calculate cumulative
        $cumulative = 0;
        return $monthly->map(function ($item) use (&$cumulative) {
            $cumulative += $item->cumulative_size;
            return [
                'month' => $item->month,
                'size' => $cumulative,
                'size_label' => $this->formatBytes($cumulative),
            ];
        })->toArray();
    }

    /**
     * Get file type distribution
     */
    private function getFileTypeDistribution(?int $divisionId): array
    {
        $query = Document::select('file_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(file_size) as total_size'));

        if ($divisionId) {
            $query->whereHas('divisions', fn ($q) => $q->where('division_id', $divisionId));
        }

        return $query->groupBy('file_type')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($item) => [
                'type' => $item->file_type ?? 'unknown',
                'label' => $this->getFileTypeLabel($item->file_type),
                'count' => $item->count,
                'total_size' => $item->total_size,
                'total_size_label' => $this->formatBytes($item->total_size),
            ])
            ->toArray();
    }

    /**
     * Get stagnant documents (not accessed in X months)
     */
    private function getStagnantDocuments(?int $divisionId, int $months = 6): array
    {
        $dateLimit = now()->subMonths($months);

        $query = Document::select('id', 'title', 'file_size', 'created_at', 'updated_at')
            ->where('updated_at', '<', $dateLimit);

        if ($divisionId) {
            $query->whereHas('divisions', fn ($q) => $q->where('division_id', $divisionId));
        }

        return $query->orderBy('updated_at')
            ->limit(10)
            ->get()
            ->map(fn ($doc) => [
                'id' => $doc->id,
                'title' => $doc->title,
                'file_size_label' => $this->formatBytes($doc->file_size),
                'last_activity' => $doc->updated_at->diffForHumans(),
            ])
            ->toArray();
    }

    /**
     * Get top uploaders
     */
    private function getTopUploaders(?int $divisionId, int $limit = 10): array
    {
        $query = Document::select('uploaded_by', DB::raw('COUNT(*) as total'), DB::raw('SUM(file_size) as total_size'))
            ->with('uploader:id,name');

        if ($divisionId) {
            $query->whereHas('divisions', fn ($q) => $q->where('division_id', $divisionId));
        }

        return $query->groupBy('uploaded_by')
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->map(fn ($item) => [
                'user_id' => $item->uploaded_by,
                'user_name' => $item->uploader?->name ?? 'Unknown',
                'total_documents' => $item->total,
                'total_size' => $item->total_size,
                'total_size_label' => $this->formatBytes($item->total_size),
            ])
            ->toArray();
    }

    /**
     * Get largest documents
     */
    private function getLargestDocuments(?int $divisionId, int $limit = 10): array
    {
        $query = Document::with(['classification', 'uploader'])
            ->orderByDesc('file_size');

        if ($divisionId) {
            $query->whereHas('divisions', fn ($q) => $q->where('division_id', $divisionId));
        }

        return $query->limit($limit)
            ->get()
            ->map(fn ($doc) => [
                'id' => $doc->id,
                'title' => $doc->title,
                'classification' => $doc->classification?->name,
                'uploader' => $doc->uploader?->name,
                'file_size' => $doc->file_size,
                'file_size_label' => $this->formatBytes($doc->file_size),
                'created_at' => $doc->created_at->format('d M Y'),
            ])
            ->toArray();
    }

    /**
     * Get division comparison
     */
    private function getDivisionComparison(): array
    {
        $divisions = Division::all();

        return $divisions->map(function ($division) {
            $documentCount = Document::whereHas('divisions', fn ($q) => $q->where('division_id', $division->id))
                ->count();
            $totalSize = Document::whereHas('divisions', fn ($q) => $q->where('division_id', $division->id))
                ->sum('file_size');

            return [
                'division_id' => $division->id,
                'division_name' => $division->name,
                'document_count' => $documentCount,
                'total_size' => $totalSize,
                'total_size_label' => $this->formatBytes($totalSize),
            ];
        })
            ->sortByDesc('document_count')
            ->values()
            ->toArray();
    }

    /**
     * Get human readable file type label
     */
    private function getFileTypeLabel(?string $mimeType): string
    {
        $labels = [
            'application/pdf' => 'PDF',
            'application/msword' => 'Word',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'Word',
            'application/vnd.ms-excel' => 'Excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'Excel',
            'application/vnd.ms-powerpoint' => 'PowerPoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'PowerPoint',
            'image/jpeg' => 'Image (JPEG)',
            'image/png' => 'Image (PNG)',
            'image/gif' => 'Image (GIF)',
            'text/plain' => 'Text',
            'application/zip' => 'ZIP Archive',
        ];

        return $labels[$mimeType] ?? ($mimeType ?? 'Unknown');
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
