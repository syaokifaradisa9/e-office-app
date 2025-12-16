<?php

namespace Modules\Inventory\Models;

use App\Models\Division;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Inventory\Database\Factories\ItemFactory;

class Item extends Model
{
    use HasFactory;

    protected static function newFactory(): ItemFactory
    {
        return ItemFactory::new();
    }

    protected $fillable = [
        'division_id',
        'category_id',
        'image_url',
        'name',
        'unit_of_measure',
        'stock',
        'description',
        'reference_item_id',
        'main_reference_item_id',
        'multiplier',
    ];

    protected function casts(): array
    {
        return [
            'division_id' => 'integer',
            'category_id' => 'integer',
            'stock' => 'integer',
            'reference_item_id' => 'integer',
            'main_reference_item_id' => 'integer',
            'multiplier' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CategoryItem::class, 'category_id');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function referenceItem(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reference_item_id');
    }

    public function mainReferenceItem(): BelongsTo
    {
        return $this->belongsTo(self::class, 'main_reference_item_id');
    }

    public function derivedItems(): HasMany
    {
        return $this->hasMany(self::class, 'reference_item_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(ItemTransaction::class);
    }

    public function warehouseOrderCarts(): HasMany
    {
        return $this->hasMany(WarehouseOrderCart::class);
    }
}
