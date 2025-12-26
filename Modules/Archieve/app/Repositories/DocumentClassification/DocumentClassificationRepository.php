<?php

namespace Modules\Archieve\Repositories\DocumentClassification;

use Modules\Archieve\Models\DocumentClassification;

interface DocumentClassificationRepository
{
    public function all();
    public function find(int $id);
    public function store(array $data);
    public function update(DocumentClassification $classification, array $data);
    public function delete(DocumentClassification $classification);
    public function getRoots();
}
