<?php

namespace Modules\Ticketing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Ticketing\Database\Factories\ChecklistFactory;

class Checklist extends Model
{
    use HasFactory;

    protected static function newFactory(): ChecklistFactory
    {
        return ChecklistFactory::new();
    }

    protected $table = 'checklists';

    protected $fillable = [
        'asset_category_id',
        'label',
        'description',
    ];

    public function assetCategory(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class);
    }
}
