<?php

namespace App\Repositories\Role;

use App\Repositories\EloquentBaseRepository;
use Spatie\Permission\Models\Role;

class EloquentRoleRepository extends EloquentBaseRepository implements RoleRepository
{
    public function __construct(Role $model)
    {
        parent::__construct($model);
    }
}
