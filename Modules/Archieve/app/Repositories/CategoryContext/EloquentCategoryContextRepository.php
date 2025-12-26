<?php

namespace Modules\Archieve\Repositories\CategoryContext;

use Modules\Archieve\Models\CategoryContext;

class EloquentCategoryContextRepository implements CategoryContextRepository
{
    public function all()
    {
        return CategoryContext::all();
    }

    public function find(int $id)
    {
        return CategoryContext::findOrFail($id);
    }

    public function store(array $data)
    {
        return CategoryContext::create($data);
    }

    public function update(CategoryContext $context, array $data)
    {
        $context->update($data);
        return $context;
    }

    public function delete(CategoryContext $context)
    {
        return $context->delete();
    }
}
