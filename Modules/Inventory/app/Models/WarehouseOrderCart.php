<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseOrderCart extends Model
{
    protected $fillable = [
        'warehouse_order_id',
        'item_id',
        'quantity',
        'delivered_quantity',
        'received_quantity',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'warehouse_order_id' => 'integer',
            'item_id' => 'integer',
            'quantity' => 'integer',
            'delivered_quantity' => 'integer',
            'received_quantity' => 'integer',
        ];
    }

    public function warehouseOrder(): BelongsTo
    {
        return $this->belongsTo(WarehouseOrder::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
