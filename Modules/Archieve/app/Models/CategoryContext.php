<?php

namespace Modules\Archieve\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Archieve\Database\Factories\CategoryContextFactory;

class CategoryContext extends Model
{
    use HasFactory;

    protected static function newFactory(): CategoryContextFactory
    {
        return CategoryContextFactory::new();
    }

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
