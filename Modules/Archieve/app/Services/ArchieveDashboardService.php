<?php

namespace Modules\Archieve\Services;

use App\Models\Division;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Archieve\Models\Category;
use Modules\Archieve\Models\Document;
use Modules\Archieve\Models\DocumentClassification;
use Modules\Archieve\Models\DivisionStorage;
use Modules\Archieve\Enums\ArchievePermission;

class ArchieveDashboardService
{
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

        $storage = DivisionStorage::where('division_id', $divisionId)->first();
        $usedSize = Document::whereHas('divisions', fn ($q) => $q->where('division_id', $divisionId))
            ->sum('file_size');

        $documentCount = Document::whereHas('divisions', fn ($q) => $q->where('division_id', $divisionId))
            ->count();

        $recentDocuments = Document::whereHas('divisions', fn ($q) => $q->where('division_id', $divisionId))
            ->with(['classification', 'uploader'])
            ->latest()
            ->limit(5)
            ->get();

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
        $totalDocuments = Document::count();
        $totalSize = Document::sum('file_size');
        
        $divisionStorageStatus = $this->getDivisionStorageStatus();
        $uploadTrend = $this->getUploadTrend();
        $classificationDistribution = $this->getClassificationDistribution();
        $fileTypeDistribution = $this->getFileTypeDistribution();
        $topUploaders = $this->getTopUploaders(5);
        $recentDocuments = Document::with(['classification', 'uploader', 'divisions'])
            ->latest()
            ->limit(10)
            ->get();

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
        $divisions = Division::with('archieveStorage')->get();
        $status = [];

        foreach ($divisions as $division) {
            $usedSize = Document::whereHas('divisions', fn ($q) => $q->where('division_id', $division->id))
                ->sum('file_size');

            $maxSize = $division->archieveStorage?->max_size ?? 0;
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
     * Get monthly upload trend (last 12 months)
     */
    private function getUploadTrend(): array
    {
        return Document::select(
            DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
            DB::raw('COUNT(*) as total_documents'),
            DB::raw('SUM(file_size) as total_size')
        )
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->toArray();
    }

    /**
     * Get category distribution for a division
     */
    private function getCategoryDistribution(?int $divisionId = null): array
    {
        $query = Category::withCount(['documents' => function ($q) use ($divisionId) {
            if ($divisionId) {
                $q->whereHas('divisions', fn ($dq) => $dq->where('division_id', $divisionId));
            }
        }]);

        return $query->having('documents_count', '>', 0)
            ->orderByDesc('documents_count')
            ->limit(10)
            ->get()
            ->map(fn ($cat) => [
                'name' => $cat->name,
                'count' => $cat->documents_count,
            ])
            ->toArray();
    }

    /**
     * Get classification distribution
     */
    private function getClassificationDistribution(): array
    {
        return DocumentClassification::whereNull('parent_id')
            ->withCount('documents')
            ->having('documents_count', '>', 0)
            ->orderByDesc('documents_count')
            ->limit(10)
            ->get()
            ->map(fn ($cls) => [
                'name' => $cls->name,
                'code' => $cls->code,
                'count' => $cls->documents_count,
            ])
            ->toArray();
    }

    /**
     * Get file type distribution
     */
    private function getFileTypeDistribution(): array
    {
        return Document::select('file_type', DB::raw('COUNT(*) as count'))
            ->groupBy('file_type')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($item) => [
                'type' => $item->file_type ?? 'unknown',
                'count' => $item->count,
            ])
            ->toArray();
    }

    /**
     * Get top uploaders
     */
    private function getTopUploaders(int $limit = 5): array
    {
        return Document::select('uploaded_by', DB::raw('COUNT(*) as total'))
            ->with('uploader:id,name')
            ->groupBy('uploaded_by')
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->map(fn ($item) => [
                'user_id' => $item->uploaded_by,
                'user_name' => $item->uploader?->name ?? 'Unknown',
                'total' => $item->total,
            ])
            ->toArray();
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
