<?php

namespace App\Repositories\Division;

use App\Models\Division;
use App\Repositories\EloquentBaseRepository;

class EloquentDivisionRepository extends EloquentBaseRepository implements DivisionRepository
{
    public function __construct(Division $model)
    {
        parent::__construct($model);
    }
}
