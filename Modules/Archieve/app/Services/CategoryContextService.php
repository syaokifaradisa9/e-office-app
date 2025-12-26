<?php

namespace Modules\Archieve\Services;

use Modules\Archieve\DataTransferObjects\StoreCategoryContextDTO;
use Modules\Archieve\Models\CategoryContext;
use Modules\Archieve\Repositories\CategoryContext\CategoryContextRepository;

class CategoryContextService
{
    public function __construct(
        private CategoryContextRepository $repository
    ) {}

    public function all()
    {
        return $this->repository->all();
    }

    public function find(int $id)
    {
        return $this->repository->find($id);
    }

    public function store(StoreCategoryContextDTO $dto)
    {
        return $this->repository->store($dto->toArray());
    }

    public function update(CategoryContext $context, StoreCategoryContextDTO $dto)
    {
        return $this->repository->update($context, $dto->toArray());
    }

    public function delete(CategoryContext $context)
    {
        return $this->repository->delete($context);
    }
}
