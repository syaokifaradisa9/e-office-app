<?php

namespace Modules\Archieve\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoryContext extends Model
{
    protected $table = 'archieve_category_contexts';
    
    protected $fillable = [
        'name',
        'description',
    ];

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'context_id');
    }
}
