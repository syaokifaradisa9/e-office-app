<?php

namespace Modules\Inventory\Models;

use App\Models\Division;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Inventory\Database\Factories\StockOpnameFactory;

class StockOpname extends Model
{
    use HasFactory;

    protected static function newFactory(): StockOpnameFactory
    {
        return StockOpnameFactory::new();
    }

    protected $fillable = [
        'user_id',
        'division_id',
        'opname_date',
        'notes',
        'status',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'division_id' => 'integer',
            'opname_date' => 'date',
            'status' => \Modules\Inventory\Enums\StockOpnameStatus::class,
            'confirmed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockOpnameItem::class);
    }
}
