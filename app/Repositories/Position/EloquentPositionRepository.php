<?php

namespace App\Repositories\Position;

use App\Models\Position;
use App\Repositories\EloquentBaseRepository;

class EloquentPositionRepository extends EloquentBaseRepository implements PositionRepository
{
    public function __construct(Position $model)
    {
        parent::__construct($model);
    }
}
