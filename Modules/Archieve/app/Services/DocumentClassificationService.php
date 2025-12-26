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
        return $this->repository->getAllWithHierarchy();
    }
}
