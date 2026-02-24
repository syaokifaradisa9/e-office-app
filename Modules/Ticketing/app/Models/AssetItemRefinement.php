<?php

namespace Modules\Ticketing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetItemRefinement extends Model
{
    protected $table = 'asset_item_refinements';

    protected $fillable = [
        'maintenance_id',
        'date',
        'description',
        'note',
        'result',
        'attachments',
    ];

    protected $casts = [
        'date' => 'date',
        'attachments' => 'array',
    ];

    public function maintenance(): BelongsTo
    {
        return $this->belongsTo(Maintenance::class);
    }
}
