<?php

namespace Modules\Archieve\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Archieve\Database\Factories\DocumentClassificationFactory;

class DocumentClassification extends Model
{
    use HasFactory;

    protected static function newFactory(): DocumentClassificationFactory
    {
        return DocumentClassificationFactory::new();
    }

    protected $table = 'archieve_document_classifications';

    protected $fillable = [
        'parent_id',
        'code',
        'name',
        'description',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(DocumentClassification::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(DocumentClassification::class, 'parent_id')->orderBy('code');
    }

    /**
     * Get all descendants recursive.
     */
    public function allChildren(): HasMany
    {
        return $this->children()->with('allChildren');
    }

    /**
     * Get documents with this classification.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'classification_id');
    }

    /**
     * Get the full hierarchy name or code.
     */
    public function getFullPathAttribute(): string
    {
        if ($this->parent) {
            return $this->parent->full_path . ' > ' . $this->name;
        }

        return $this->name;
    }
}
