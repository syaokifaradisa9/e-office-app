<?php

namespace Modules\VisitorManagement\Repositories\Purpose;

use Modules\VisitorManagement\Models\VisitorPurpose;
use Illuminate\Database\Eloquent\Collection;

interface PurposeRepository
{
    public function all(): Collection;
    public function findById(int $id): ?VisitorPurpose;
    public function create(array $data): VisitorPurpose;
    public function update(VisitorPurpose $purpose, array $data): bool;
    public function delete(VisitorPurpose $purpose): bool;
    public function hasVisitors(VisitorPurpose $purpose): bool;
}
