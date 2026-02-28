<?php

namespace Modules\Ticketing\Models;

use App\Models\User;
use App\Models\Division;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Ticketing\Enums\AssetItemStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Ticketing\Database\Factories\AssetItemFactory;


class AssetItem extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory(): AssetItemFactory
    {
        return AssetItemFactory::new();
    }

    protected $table = 'asset_items';

    protected $fillable = [
        'asset_category_id',
        'merk',
        'model',
        'serial_number',
        'division_id',
        'another_attributes',
        'last_maintenance_date',
        'status',
    ];

    protected $casts = [
        'another_attributes' => 'array',
        'last_maintenance_date' => 'date',
        'status' => AssetItemStatus::class,

    ];

    public function assetCategory(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'asset_item_user');
    }

    public function maintenances(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Maintenance::class);
    }

    public function tickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
