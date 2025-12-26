<?php

namespace Modules\Archieve\Repositories\Document;

use Modules\Archieve\Models\Document;
use Illuminate\Database\Eloquent\Builder;

interface DocumentRepository
{
    public function all();
    public function find(int $id);
    public function store(array $data): Document;
    public function update(Document $document, array $data): Document;
    public function delete(Document $document): bool;
    
    public function syncCategories(Document $document, array $categoryIds): void;
    public function syncDivisions(Document $document, array $divisionData): void;
    public function syncUsers(Document $document, array $userIds): void;
    
    public function queryForDivision(int $divisionId): Builder;
    public function queryForUser(int $userId): Builder;
    public function queryForAll(): Builder;
    public function searchQuery(): Builder;
    public function getClassificationDocumentCounts(Builder $query): array;
    public function countByDivision(?int $divisionId = null): int;
    public function sumSizeByDivision(?int $divisionId = null): int;
    public function countThisMonthByDivision(?int $divisionId = null): int;
    public function countLastMonthByDivision(?int $divisionId = null): int;
    public function getMonthlyTrend(?int $divisionId = null, int $months = 12): array;
    public function getFileTypeDistribution(?int $divisionId = null): array;
    public function getStagnantDocuments(?int $divisionId = null, int $months = 6, int $limit = 10): \Illuminate\Database\Eloquent\Collection;
    public function getTopUploaders(?int $divisionId = null, int $limit = 10): \Illuminate\Database\Eloquent\Collection;
    public function getLargestDocuments(?int $divisionId = null, int $limit = 10): \Illuminate\Database\Eloquent\Collection;
    public function getLatestByDivision(?int $divisionId = null, int $limit = 10): \Illuminate\Database\Eloquent\Collection;
}

