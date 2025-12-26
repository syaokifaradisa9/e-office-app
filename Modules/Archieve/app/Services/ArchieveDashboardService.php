<?php

namespace Modules\Archieve\Services;

use App\Models\User;
use Modules\Archieve\Repositories\Category\CategoryRepository;
use Modules\Archieve\Repositories\Document\DocumentRepository;
use Modules\Archieve\Repositories\DocumentClassification\DocumentClassificationRepository;
use Modules\Archieve\Repositories\DivisionStorage\DivisionStorageRepository;
use App\Repositories\Division\DivisionRepository;
use Modules\Archieve\Enums\ArchievePermission;

class ArchieveDashboardService
{
    public function __construct(
        private DocumentRepository $documentRepository,
        private CategoryRepository $categoryRepository,
        private DocumentClassificationRepository $classificationRepository,
        private DivisionStorageRepository $storageRepository,
        private DivisionRepository $divisionRepository
    ) {}

    /**
     * Get dashboard tabs for the authenticated user
     */
    public function getDashboardTabs(): array
    {
        $user = auth()->user();
        $tabs = [];

        // Tab: Arsip Divisi Saya
        if ($user->can(ArchievePermission::ViewDashboardDivision->value) && $user->division_id) {
            $tabs[] = $this->getDivisionTab($user);
        }

        // Tab: Arsip Keseluruhan
        if ($user->can(ArchievePermission::ViewDashboardAll->value)) {
            $tabs[] = $this->getAllTab();
        }

        return $tabs;
    }

    /**
     * Get division-specific dashboard data
     */
    private function getDivisionTab(User $user): array
    {
        $divisionId = $user->division_id;

        $storage = $this->storageRepository->findByDivision($divisionId);
        $usedSize = $this->documentRepository->sumSizeByDivision($divisionId);
        $documentCount = $this->documentRepository->countByDivision($divisionId);
        $recentDocuments = $this->documentRepository->getLatestByDivision($divisionId, 5);
        $categoryDistribution = $this->getCategoryDistribution($divisionId);

        return [
            'id' => 'division',
            'label' => 'Arsip ' . ($user->division?->name ?? 'Divisi'),
            'icon' => 'building',
            'type' => 'division',
            'data' => [
                'storage' => [
                    'used' => $usedSize,
                    'used_label' => $this->formatBytes($usedSize),
                    'max' => $storage?->max_size ?? 0,
                    'max_label' => $this->formatBytes($storage?->max_size ?? 0),
                    'percentage' => $storage && $storage->max_size > 0 
                        ? round(($usedSize / $storage->max_size) * 100, 1) 
                        : 0,
                ],
                'document_count' => $documentCount,
                'recent_documents' => $recentDocuments,
                'category_distribution' => $categoryDistribution,
                'division_name' => $user->division?->name,
            ],
        ];
    }

    /**
     * Get global dashboard data
     */
    private function getAllTab(): array
    {
        $totalDocuments = $this->documentRepository->countByDivision(null);
        $totalSize = $this->documentRepository->sumSizeByDivision(null);
        
        $divisionStorageStatus = $this->getDivisionStorageStatus();
        $uploadTrend = $this->documentRepository->getMonthlyTrend(null);
        $classificationDistribution = $this->classificationRepository->getDistribution(null);
        $fileTypeDistribution = $this->documentRepository->getFileTypeDistribution(null);
        $topUploaders = $this->getTopUploadersFormatted(5);
        $recentDocuments = $this->documentRepository->getLatestByDivision(null, 10);

        return [
            'id' => 'all',
            'label' => 'Keseluruhan',
            'icon' => 'globe',
            'type' => 'overview',
            'data' => [
                'total_documents' => $totalDocuments,
                'total_size' => $totalSize,
                'total_size_label' => $this->formatBytes($totalSize),
                'division_storage_status' => $divisionStorageStatus,
                'upload_trend' => $uploadTrend,
                'classification_distribution' => $classificationDistribution,
                'file_type_distribution' => $fileTypeDistribution,
                'top_uploaders' => $topUploaders,
                'recent_documents' => $recentDocuments,
            ],
        ];
    }

    /**
     * Get storage status for all divisions
     */
    private function getDivisionStorageStatus(): array
    {
        $divisions = $this->divisionRepository->all();
        $status = [];

        foreach ($divisions as $division) {
            $usedSize = $this->documentRepository->sumSizeByDivision($division->id);
            $storage = $this->storageRepository->findByDivision($division->id);
            $maxSize = $storage?->max_size ?? 0;
            $percentage = $maxSize > 0 ? round(($usedSize / $maxSize) * 100, 1) : 0;

            $status[] = [
                'division_id' => $division->id,
                'division_name' => $division->name,
                'used_size' => $usedSize,
                'used_size_label' => $this->formatBytes($usedSize),
                'max_size' => $maxSize,
                'max_size_label' => $this->formatBytes($maxSize),
                'percentage' => $percentage,
                'status' => $percentage >= 90 ? 'critical' : ($percentage >= 70 ? 'warning' : 'stable'),
            ];
        }

        return $status;
    }

    /**
     * Get category distribution for a division
     */
    private function getCategoryDistribution(?int $divisionId = null): array
    {
        return $this->categoryRepository->getRankings($divisionId, 'most', 10);
    }

    /**
     * Get top uploaders formatted for dashboard
     */
    private function getTopUploadersFormatted(int $limit = 5): array
    {
        $uploaders = $this->documentRepository->getTopUploaders(null, $limit);
        
        return $uploaders->map(fn ($item) => [
            'user_id' => $item->uploaded_by,
            'user_name' => $item->uploader?->name ?? 'Unknown',
            'total' => $item->total,
        ])->toArray();
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
