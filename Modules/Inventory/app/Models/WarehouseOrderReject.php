<?php

namespace Modules\Inventory\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseOrderReject extends Model
{
    protected $fillable = [
        'warehouse_order_id',
        'user_id',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'warehouse_order_id' => 'integer',
            'user_id' => 'integer',
        ];
    }

    public function warehouseOrder(): BelongsTo
    {
        return $this->belongsTo(WarehouseOrder::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
