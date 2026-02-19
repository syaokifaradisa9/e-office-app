<?php

namespace Modules\Ticketing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Checklist extends Model
{
    use HasFactory;

    protected $table = 'checklists';

    protected $fillable = [
        'asset_model_id',
        'label',
        'description',
    ];

    public function assetModel(): BelongsTo
    {
        return $this->belongsTo(AssetModel::class);
    }
}
