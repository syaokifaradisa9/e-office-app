<?php

namespace Modules\Ticketing\Models;

use App\Models\Division;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Ticketing\Enums\AssetCategoryType;

use Modules\Ticketing\Database\Factories\AssetCategoryFactory;

class AssetCategory extends Model
{
    use HasFactory;

    protected static function newFactory(): AssetCategoryFactory
    {
        return AssetCategoryFactory::new();
    }

    protected $table = 'asset_categories';

    protected $fillable = [
        'name',
        'type',
        'division_id',
        'maintenance_count',
    ];

    protected $casts = [
        'type' => AssetCategoryType::class,
        'maintenance_count' => 'integer',
    ];

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(Checklist::class);
    }

    public function assetItems(): HasMany
    {
        return $this->hasMany(AssetItem::class);
    }
}
