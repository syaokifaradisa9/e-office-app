<?php

namespace Modules\Ticketing\Models;

use App\Models\Division;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetItem extends Model
{
    use HasFactory;

    protected $table = 'asset_items';

    protected $fillable = [
        'asset_model_id',
        'merk',
        'model',
        'serial_number',
        'division_id',
        'another_attributes',
    ];

    protected $casts = [
        'another_attributes' => 'array',
    ];

    public function assetModel(): BelongsTo
    {
        return $this->belongsTo(AssetModel::class, 'asset_model_id');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'asset_item_user');
    }
}
