<?php

namespace Modules\Inventory\Models;

use App\Models\Division;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Inventory\Database\Factories\WarehouseOrderFactory;
use Modules\Inventory\Enums\WarehouseOrderStatus;

class WarehouseOrder extends Model
{
    use HasFactory;

    protected static function newFactory(): WarehouseOrderFactory
    {
        return WarehouseOrderFactory::new();
    }

    protected $fillable = [
        'user_id',
        'division_id',
        'order_number',
        'description',
        'notes',
        'status',
        'accepted_date',
        'delivery_images',
        'delivery_date',
        'delivered_by',
        'receipt_date',
        'receipt_images',
        'received_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => WarehouseOrderStatus::class,
            'accepted_date' => 'date',
            'delivery_images' => 'array',
            'delivery_date' => 'date',
            'receipt_date' => 'date',
            'receipt_images' => 'array',
            'user_id' => 'integer',
            'division_id' => 'integer',
            'delivered_by' => 'integer',
            'received_by' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deliveredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivered_by');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function carts(): HasMany
    {
        return $this->hasMany(WarehouseOrderCart::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(WarehouseOrderCart::class);
    }

    public function rejects(): HasMany
    {
        return $this->hasMany(WarehouseOrderReject::class);
    }

    public function latestReject(): HasOne
    {
        return $this->hasOne(WarehouseOrderReject::class)->latestOfMany();
    }
}
