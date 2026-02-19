<?php

namespace Modules\Ticketing\Repositories\Checklist;

use Modules\Ticketing\Models\Checklist;
use Illuminate\Database\Eloquent\Collection;

interface ChecklistRepository
{
    public function getAllByAssetModelId(int $assetModelId): Collection;
    public function findById(int $id): ?Checklist;
    public function store(array $data): Checklist;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
}
