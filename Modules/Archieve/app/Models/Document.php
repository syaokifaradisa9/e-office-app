<?php

namespace Modules\Archieve\Models;

use App\Models\Division;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Archieve\Database\Factories\DocumentFactory;

class Document extends Model
{
    use HasFactory;

    protected static function newFactory(): DocumentFactory
    {
        return DocumentFactory::new();
    }

    protected $table = 'archieve_documents';

    protected $fillable = [
        'title',
        'description',
        'classification_id',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'uploaded_by',
    ];

    protected $appends = ['file_size_label'];

    public function classification(): BelongsTo
    {
        return $this->belongsTo(DocumentClassification::class, 'classification_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'archieve_document_category', 'document_id', 'category_id')
            ->withTimestamps();
    }

    public function divisions(): BelongsToMany
    {
        return $this->belongsToMany(Division::class, 'archieve_document_division', 'document_id', 'division_id')
            ->withPivot('allocated_size')
            ->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'archieve_document_user', 'document_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * Get human readable file size.
     */
    public function getFileSizeLabelAttribute(): string
    {
        return $this->formatBytes($this->file_size);
    }

    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
