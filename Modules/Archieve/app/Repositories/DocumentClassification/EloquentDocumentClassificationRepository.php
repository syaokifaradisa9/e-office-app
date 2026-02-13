<?php

namespace Modules\Archieve\Repositories\DocumentClassification;

use Modules\Archieve\Models\DocumentClassification;

class EloquentDocumentClassificationRepository implements DocumentClassificationRepository
{
    public function all()
    {
        return DocumentClassification::with('parent')->orderBy('code')->get();
    }

    public function find(int $id)
    {
        return DocumentClassification::findOrFail($id);
    }

    public function store(array $data)
    {
        return DocumentClassification::create($data);
    }

    public function update(DocumentClassification $classification, array $data)
    {
        $classification->update($data);
        return $classification;
    }

    public function delete(DocumentClassification $classification)
    {
        return $classification->delete();
    }

    public function getRoots()
    {
        return DocumentClassification::whereNull('parent_id')->orderBy('code')->get();
    }

    public function getRankings(?int $divisionId = null, int $limit = 10): array
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

    public function getDistribution(?int $divisionId = null, int $limit = 10): array
    {
        $query = DocumentClassification::whereNull('parent_id')
            ->withCount(['documents' => function ($q) use ($divisionId) {
                if ($divisionId) {
                    $q->whereHas('divisions', fn ($dq) => $dq->where('division_id', $divisionId));
                }
            }]);

        if ($divisionId) {
            $query->whereHas('documents', fn ($q) => $q->whereHas('divisions', fn ($dq) => $dq->where('division_id', $divisionId)));
        } else {
            $query->has('documents');
        }

        return $query->orderByDesc('documents_count')
            ->limit($limit)
            ->get()
            ->map(fn ($cls) => [
                'name' => $cls->name,
                'code' => $cls->code,
                'count' => $cls->documents_count,
            ])
            ->toArray();
    }

    public function getAllWithHierarchy(): \Illuminate\Database\Eloquent\Collection
    {
        $classifications = DocumentClassification::whereNull('parent_id')
            ->with(['children' => function ($query) {
                $query->orderBy('code');
            }])
            ->orderBy('code')
            ->get();

        foreach ($classifications as $classification) {
            $this->loadChildrenRecursively($classification);
        }

        return $classifications;
    }

    private function loadChildrenRecursively($classification)
    {
        if ($classification->children->isNotEmpty()) {
            foreach ($classification->children as $child) {
                $child->load(['children' => function ($query) {
                    $query->orderBy('code');
                }]);
                $this->loadChildrenRecursively($child);
            }
        }
    }
}

