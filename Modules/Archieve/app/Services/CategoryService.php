<?php

namespace Modules\Archieve\Services;

use Modules\Archieve\DataTransferObjects\StoreCategoryDTO;
use Modules\Archieve\Models\Category;
use Modules\Archieve\Repositories\Category\CategoryRepository;

class CategoryService
{
    public function __construct(
        private CategoryRepository $categoryRepository
    ) {}

    public function store(StoreCategoryDTO $dto): Category
    {
        return $this->categoryRepository->create($dto->toArray());
    }

    public function update(Category $category, StoreCategoryDTO $dto): bool
    {
        return $this->categoryRepository->update($category, $dto->toArray());
    }

    public function delete(Category $category): bool
    {
        // Check for relationships before deleting if necessary
        return $this->categoryRepository->delete($category);
    }
}
