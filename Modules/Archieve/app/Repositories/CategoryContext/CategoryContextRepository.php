<?php

namespace Modules\Archieve\Repositories\CategoryContext;

use Modules\Archieve\Models\CategoryContext;

interface CategoryContextRepository
{
    public function all();
    public function allWithCategories();
    public function find(int $id);
    public function store(array $data);
    public function update(CategoryContext $context, array $data);
    public function delete(CategoryContext $context);
}
