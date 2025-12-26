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
}
