<?php

namespace Modules\Ticketing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Ticketing\Enums\TicketStatus;
use Modules\Ticketing\Enums\TicketPriority;

class Ticket extends Model
{
    protected $fillable = [
        'user_id',
        'asset_item_id',
        'status',
        'subject',
        'description',
        'priority',
        'real_priority',
        'priority_reason',
        'attachments',
        'diagnose',
        'follow_up',
        'note',
        'confirm_note',
        'process_note',
        'process_attachments',
        'rating',
        'feedback_description',
        'confirmed_by',
        'processed_by',
        'confirmed_at',
        'processed_at',
        'finished_at',
        'closed_at',
    ];

    protected $casts = [
        'status' => TicketStatus::class,
        'priority' => TicketPriority::class,
        'real_priority' => TicketPriority::class,
        'attachments' => 'array',
        'process_attachments' => 'array',
        'confirmed_at' => 'datetime',
        'processed_at' => 'datetime',
        'finished_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assetItem(): BelongsTo
    {
        return $this->belongsTo(AssetItem::class);
    }

    public function confirmedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function processedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function refinements(): HasMany
    {
        return $this->hasMany(AssetItemRefinement::class);
    }
}
