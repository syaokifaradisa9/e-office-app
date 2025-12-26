<?php

namespace Modules\Archieve\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Archieve\Repositories\Category\CategoryRepository;
use Modules\Archieve\Repositories\Document\DocumentRepository;
use Modules\Archieve\Repositories\DocumentClassification\DocumentClassificationRepository;
use Modules\Archieve\Repositories\DivisionStorage\DivisionStorageRepository;
use App\Repositories\Division\DivisionRepository;

class ArchieveReportService
{
    public function __construct(
        private DocumentRepository $documentRepository,
        private CategoryRepository $categoryRepository,
        private DocumentClassificationRepository $classificationRepository,
        private DivisionStorageRepository $storageRepository,
        private DivisionRepository $divisionRepository
    ) {}

    /**
     * Get division report data
     */
    public function getDivisionReportData(User $user): array
    {
        $divisionId = $user->division_id;

        return [
            'division_name' => $user->division_id ? $this->divisionRepository->find($user->division_id)?->name : null,
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
        $divisions = $this->divisionRepository->all();

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
                'division_comparison' => $this->getDivisionComparison($divisions),
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
        $totalDocuments = $this->documentRepository->countByDivision($divisionId);
        $totalSize = $this->documentRepository->sumSizeByDivision($divisionId);
        $thisMonthDocuments = $this->documentRepository->countThisMonthByDivision($divisionId);
        $lastMonthDocuments = $this->documentRepository->countLastMonthByDivision($divisionId);

        $growthRate = $lastMonthDocuments > 0 
            ? round((($thisMonthDocuments - $lastMonthDocuments) / $lastMonthDocuments) * 100, 1)
            : 0;

        $storage = null;
        if ($divisionId) {
            $storage = $this->storageRepository->findByDivision($divisionId);
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
        return $this->documentRepository->getMonthlyTrend($divisionId);
    }

    /**
     * Get category rankings
     */
    private function getCategoryRankings(?int $divisionId, string $type = 'most', int $limit = 10): array
    {
        return $this->categoryRepository->getRankings($divisionId, $type, $limit);
    }

    /**
     * Get classification rankings
     */
    private function getClassificationRankings(?int $divisionId, int $limit = 10): array
    {
        return $this->classificationRepository->getRankings($divisionId, $limit);
    }

    /**
     * Get storage trend over time
     */
    private function getStorageTrend(?int $divisionId): array
    {
        $monthly = $this->documentRepository->getMonthlyTrend($divisionId);

        // Calculate cumulative
        $cumulative = 0;
        return array_map(function ($item) use (&$cumulative) {
            $cumulative += $item['total_size'];
            return [
                'month' => $item['month'],
                'size' => $cumulative,
                'size_label' => $this->formatBytes($cumulative),
            ];
        }, $monthly);
    }

    /**
     * Get file type distribution
     */
    private function getFileTypeDistribution(?int $divisionId): array
    {
        $distribution = $this->documentRepository->getFileTypeDistribution($divisionId);

        return array_map(fn ($item) => [
            'type' => $item['file_type'] ?? 'unknown',
            'label' => $this->getFileTypeLabel($item['file_type']),
            'count' => $item['count'],
            'total_size' => $item['total_size'],
            'total_size_label' => $this->formatBytes($item['total_size']),
        ], $distribution);
    }

    /**
     * Get stagnant documents (not accessed in X months)
     */
    private function getStagnantDocuments(?int $divisionId, int $months = 6): array
    {
        $documents = $this->documentRepository->getStagnantDocuments($divisionId, $months, 10);

        return $documents->map(fn ($doc) => [
            'id' => $doc->id,
            'title' => $doc->title,
            'file_size_label' => $this->formatBytes($doc->file_size),
            'last_activity' => $doc->updated_at->diffForHumans(),
        ])->toArray();
    }

    /**
     * Get top uploaders
     */
    private function getTopUploaders(?int $divisionId, int $limit = 10): array
    {
        $uploaders = $this->documentRepository->getTopUploaders($divisionId, $limit);

        return $uploaders->map(fn ($item) => [
            'user_id' => $item->uploaded_by,
            'user_name' => $item->uploader?->name ?? 'Unknown',
            'total_documents' => $item->total,
            'total_size' => $item->total_size,
            'total_size_label' => $this->formatBytes($item->total_size),
        ])->toArray();
    }

    /**
     * Get largest documents
     */
    private function getLargestDocuments(?int $divisionId, int $limit = 10): array
    {
        $documents = $this->documentRepository->getLargestDocuments($divisionId, $limit);

        return $documents->map(fn ($doc) => [
            'id' => $doc->id,
            'title' => $doc->title,
            'classification' => $doc->classification?->name,
            'uploader' => $doc->uploader?->name,
            'file_size' => $doc->file_size,
            'file_size_label' => $this->formatBytes($doc->file_size),
            'created_at' => $doc->created_at->format('d M Y'),
        ])->toArray();
    }

    /**
     * Get division comparison
     */
    private function getDivisionComparison($divisions): array
    {
        return $divisions->map(function ($division) {
            $documentCount = $this->documentRepository->countByDivision($division->id);
            $totalSize = $this->documentRepository->sumSizeByDivision($division->id);

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
