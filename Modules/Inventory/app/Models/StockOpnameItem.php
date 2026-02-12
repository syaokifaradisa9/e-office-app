<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOpnameItem extends Model
{
    protected $fillable = [
        'stock_opname_id',
        'item_id',
        'system_stock',
        'physical_stock',
        'notes',
        'final_stock',
        'final_notes',
    ];

    protected function casts(): array
    {
        return [
            'stock_opname_id' => 'integer',
            'item_id' => 'integer',
            'system_stock' => 'integer',
            'physical_stock' => 'integer',
            'final_stock' => 'integer',
        ];
    }

    public function stockOpname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function getDifferenceAttribute(): ?int
    {
        if (is_null($this->physical_stock)) {
            return null;
        }

        return $this->physical_stock - $this->system_stock;
    }
}
