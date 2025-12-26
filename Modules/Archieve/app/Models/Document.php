<?php

namespace Modules\Archieve\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Document extends Model
{
    protected $table = 'archieve_documents';

    protected $fillable = [
        'title',
        'file_path',
        'description',
    ];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'archieve_document_category');
    }
}
