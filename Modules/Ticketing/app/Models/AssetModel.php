<?php

namespace Modules\Ticketing\Models;

use App\Models\Division;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Ticketing\Enums\AssetModelType;

class AssetModel extends Model
{
    use HasFactory;

    protected $table = 'asset_models';

    protected $fillable = [
        'name',
        'type',
        'division_id',
        'maintenance_count',
    ];

    protected $casts = [
        'type' => AssetModelType::class,
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

