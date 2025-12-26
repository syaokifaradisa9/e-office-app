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
}
