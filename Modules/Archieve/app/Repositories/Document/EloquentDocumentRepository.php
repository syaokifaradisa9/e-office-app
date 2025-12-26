<?php

namespace Modules\Archieve\Repositories\Document;

use Modules\Archieve\Models\Document;
use Illuminate\Database\Eloquent\Builder;

class EloquentDocumentRepository implements DocumentRepository
{
    public function all()
    {
        return Document::with(['classification', 'categories', 'divisions', 'users', 'uploader'])->get();
    }

    public function find(int $id)
    {
        return Document::with(['classification', 'categories', 'divisions', 'users', 'uploader'])->findOrFail($id);
    }

    public function store(array $data): Document
    {
        return Document::create($data);
    }

    public function update(Document $document, array $data): Document
    {
        $document->update($data);
        return $document;
    }

    public function delete(Document $document): bool
    {
        return $document->delete();
    }

    public function syncCategories(Document $document, array $categoryIds): void
    {
        $document->categories()->sync($categoryIds);
    }

    public function syncDivisions(Document $document, array $divisionData): void
    {
        // $divisionData format: [division_id => ['allocated_size' => x], ...]
        $document->divisions()->sync($divisionData);
    }

    public function syncUsers(Document $document, array $userIds): void
    {
        $document->users()->sync($userIds);
    }

    public function queryForDivision(int $divisionId): Builder
    {
        return Document::with(['classification', 'categories', 'uploader'])
            ->whereHas('divisions', function ($q) use ($divisionId) {
                $q->where('divisions.id', $divisionId);
            });
    }

    public function queryForUser(int $userId): Builder
    {
        return Document::with(['classification', 'categories', 'uploader'])
            ->whereHas('users', function ($q) use ($userId) {
                $q->where('users.id', $userId);
            });
    }

    public function queryForAll(): Builder
    {
        return Document::with(['classification', 'categories', 'divisions', 'users', 'uploader']);
    }

    public function searchQuery()
    {
        return Document::query();
    }

    public function getClassificationDocumentCounts($query)
    {
        return (clone $query)->select('classification_id', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->groupBy('classification_id')
            ->pluck('count', 'classification_id')
            ->toArray();
    }

    public function countByDivision(?int $divisionId = null): int
    {
        $query = Document::query();
        if ($divisionId) {
            $query->whereHas('divisions', fn ($q) => $q->where('division_id', $divisionId));
        }
        return $query->count();
    }

    public function sumSizeByDivision(?int $divisionId = null): int
    {
        $query = Document::query();
        if ($divisionId) {
            $query->whereHas('divisions', fn ($q) => $q->where('division_id', $divisionId));
        }
        return (int) $query->sum('file_size');
    }

    public function countThisMonthByDivision(?int $divisionId = null): int
    {
        $query = Document::query();
        if ($divisionId) {
            $query->whereHas('divisions', fn ($q) => $q->where('division_id', $divisionId));
        }
        return $query->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
    }

    public function countLastMonthByDivision(?int $divisionId = null): int
    {
        $query = Document::query();
        if ($divisionId) {
            $query->whereHas('divisions', fn ($q) => $q->where('division_id', $divisionId));
        }
        return $query->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();
    }

    public function getMonthlyTrend(?int $divisionId = null, int $months = 12): array
    {
        $query = Document::select(
            \Illuminate\Support\Facades\DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
            \Illuminate\Support\Facades\DB::raw('COUNT(*) as total_documents'),
            \Illuminate\Support\Facades\DB::raw('SUM(file_size) as total_size')
        );

        if ($divisionId) {
            $query->whereHas('divisions', fn ($q) => $q->where('division_id', $divisionId));
        }

        return $query->where('created_at', '>=', now()->subMonths($months))
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->toArray();
    }

    public function getFileTypeDistribution(?int $divisionId = null): array
    {
        $query = Document::select('file_type', \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'), \Illuminate\Support\Facades\DB::raw('SUM(file_size) as total_size'));

        if ($divisionId) {
            $query->whereHas('divisions', fn ($q) => $q->where('division_id', $divisionId));
        }

        return $query->groupBy('file_type')
            ->orderByDesc('count')
            ->get()
            ->toArray();
    }

    public function getStagnantDocuments(?int $divisionId = null, int $months = 6, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        $dateLimit = now()->subMonths($months);

        $query = Document::where('updated_at', '<', $dateLimit);

        if ($divisionId) {
            $query->whereHas('divisions', fn ($q) => $q->where('division_id', $divisionId));
        }

        return $query->orderBy('updated_at')
            ->limit($limit)
            ->get();
    }

    public function getTopUploaders(?int $divisionId = null, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        $query = Document::select('uploaded_by', \Illuminate\Support\Facades\DB::raw('COUNT(*) as total'), \Illuminate\Support\Facades\DB::raw('SUM(file_size) as total_size'))
            ->with('uploader:id,name');

        if ($divisionId) {
            $query->whereHas('divisions', fn ($q) => $q->where('division_id', $divisionId));
        }

        return $query->groupBy('uploaded_by')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();
    }

    public function getLargestDocuments(?int $divisionId = null, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        $query = Document::with(['classification', 'uploader'])
            ->orderByDesc('file_size');

        if ($divisionId) {
            $query->whereHas('divisions', fn ($q) => $q->where('division_id', $divisionId));
        }

        return $query->limit($limit)->get();
    }

    public function getLatestByDivision(?int $divisionId = null, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        $query = Document::with(['classification', 'uploader']);
        
        if ($divisionId) {
            $query->whereHas('divisions', fn ($q) => $q->where('division_id', $divisionId));
        }

        return $query->latest()->limit($limit)->get();
    }
}
