<?php

namespace Modules\Archieve\Repositories\DivisionStorage;

use Modules\Archieve\Models\DivisionStorage;

interface DivisionStorageRepository
{
    public function all();
    public function find(int $id);
    public function findByDivision(int $divisionId);
    public function store(array $data);
    public function update(DivisionStorage $storage, array $data);
    public function delete(DivisionStorage $storage);
}
