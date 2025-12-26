<?php

namespace Modules\Archieve\Repositories\Category;

use Modules\Archieve\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class EloquentCategoryRepository implements CategoryRepository
{
    public function all(): Collection
    {
        return Category::all();
    }

    public function findById(int $id): ?Category
    {
        return Category::find($id);
    }

    public function create(array $data): Category
    {
        return Category::create($data);
    }

    public function update(Category $category, array $data): bool
    {
        return $category->update($data);
    }

    public function delete(Category $category): bool
    {
        return $category->delete();
    }

    public function getRankings(?int $divisionId = null, string $type = 'most', int $limit = 10): array
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
}

