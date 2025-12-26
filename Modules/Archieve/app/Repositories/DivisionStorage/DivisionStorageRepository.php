<?php

namespace Modules\Archieve\Repositories\DivisionStorage;

use Modules\Archieve\Models\DivisionStorage;

interface DivisionStorageRepository
{
    public function all();
    public function find(int $id);
    public function findByDivision(int $divisionId);
    public function findOrCreateByDivision(int $divisionId, array $defaults = []): DivisionStorage;
    public function incrementUsedSize(int $divisionId, int $size): void;
    public function decrementUsedSize(int $divisionId, int $size): void;
    public function store(array $data);

    public function update(DivisionStorage $storage, array $data);
    public function delete(DivisionStorage $storage);
}
