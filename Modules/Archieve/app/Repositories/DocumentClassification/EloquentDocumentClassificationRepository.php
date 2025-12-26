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
}
