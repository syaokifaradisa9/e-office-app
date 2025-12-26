<?php

namespace Modules\Archieve\Services;

use Modules\Archieve\DataTransferObjects\StoreDocumentClassificationDTO;
use Modules\Archieve\Models\DocumentClassification;
use Modules\Archieve\Repositories\DocumentClassification\DocumentClassificationRepository;

class DocumentClassificationService
{
    public function __construct(
        private DocumentClassificationRepository $repository
    ) {}

    public function all()
    {
        return $this->repository->all();
    }

    public function find(int $id)
    {
        return $this->repository->find($id);
    }

    public function store(StoreDocumentClassificationDTO $dto)
    {
        return $this->repository->store($dto->toArray());
    }

    public function update(DocumentClassification $classification, StoreDocumentClassificationDTO $dto)
    {
        return $this->repository->update($classification, $dto->toArray());
    }

    public function delete(DocumentClassification $classification)
    {
        return $this->repository->delete($classification);
    }

    public function getRoots()
    {
        return $this->repository->getRoots();
    }

    /**
     * Get all classifications with full nested hierarchy.
     */
    public function getAllWithHierarchy()
    {
        return DocumentClassification::whereNull('parent_id')
            ->with(['children' => function ($query) {
                $query->orderBy('code');
            }])
            ->orderBy('code')
            ->get()
            ->map(function ($classification) {
                return $this->loadChildrenRecursively($classification);
            });
    }

    /**
     * Recursively load children for a classification.
     */
    private function loadChildrenRecursively($classification)
    {
        if ($classification->children->isNotEmpty()) {
            $classification->children = $classification->children->map(function ($child) {
                $child->load(['children' => function ($query) {
                    $query->orderBy('code');
                }]);
                return $this->loadChildrenRecursively($child);
            });
        }
        return $classification;
    }
}
