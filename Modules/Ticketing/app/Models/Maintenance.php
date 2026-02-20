<?php

namespace Modules\Ticketing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Modules\Ticketing\Enums\MaintenanceStatus;

class Maintenance extends Model
{
    protected $fillable = [
        'asset_item_id',
        'estimation_date',
        'actual_date',
        'note',
        'status',
        'user_id',
        'checklist_results',
        'attachments',
    ];

    protected $casts = [
        'estimation_date' => 'date',
        'actual_date' => 'date',
        'status' => MaintenanceStatus::class,
        'checklist_results' => 'array',
        'attachments' => 'array',
    ];

    public function assetItem(): BelongsTo
    {
        return $this->belongsTo(AssetItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function checklists()
    {
        return $this->hasMany(MaintenanceChecklist::class);
    }
}
