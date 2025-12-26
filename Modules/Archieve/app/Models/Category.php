<?php

namespace Modules\Archieve\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Archieve\Enums\CategoryType;

class Category extends Model
{
    protected $table = 'archieve_categories';

    protected $fillable = [
        'name',
        'context_id',
        'description',
    ];

    public function context(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(CategoryContext::class, 'context_id');
    }

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'archieve_document_category');
    }
}
