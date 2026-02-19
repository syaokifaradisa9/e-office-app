<?php

namespace Modules\Ticketing\Models;

use App\Models\Division;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Ticketing\Enums\AssetModelType;

class AssetModel extends Model
{
    use HasFactory;

    protected $table = 'asset_models';

    protected $fillable = [
        'name',
        'type',
        'division_id',
    ];

    protected $casts = [
        'type' => AssetModelType::class,
    ];

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }
}
