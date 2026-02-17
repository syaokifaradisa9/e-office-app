<?php

namespace Modules\Inventory\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Inventory\Database\Factories\ItemTransactionFactory;
use Modules\Inventory\Enums\ItemTransactionType;

class ItemTransaction extends Model
{
    use HasFactory;

    protected static function newFactory(): ItemTransactionFactory
    {
        return ItemTransactionFactory::new();
    }

    protected $fillable = [
        'date',
        'type',
        'item_id',
        'quantity',
        'user_id',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'type' => ItemTransactionType::class,
            'item_id' => 'integer',
            'quantity' => 'integer',
            'user_id' => 'integer',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
