<?php

namespace Modules\Archieve\Repositories\Category;

use Modules\Archieve\Models\Category;
use Illuminate\Database\Eloquent\Collection;

interface CategoryRepository
{
    public function all(): Collection;
    public function findById(int $id): ?Category;
    public function create(array $data): Category;
    public function update(Category $category, array $data): bool;
    public function delete(Category $category): bool;
    public function getRankings(?int $divisionId = null, string $type = 'most', int $limit = 10): array;
}

