<?php

namespace Modules\VisitorManagement\Models;

use App\Models\User;
use App\Models\Division;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;
use Modules\VisitorManagement\Enums\VisitorStatus;

class Visitor extends Model
{
    use HasFactory;

    protected $fillable = [
        'visitor_name',
        'phone_number',
        'organization',
        'photo_url',
        'division_id',
        'purpose_id',
        'purpose_detail',
        'visitor_count',
        'check_in_at',
        'check_out_at',
        'status',
        'admin_note',
        'confirmed_by',
        'confirmed_at',
    ];

    protected $casts = [
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'visitor_count' => 'integer',
        'status' => VisitorStatus::class,
    ];

    protected function photoUrl(): Attribute
    {
        return Attribute::make(
            get: function (?string $value) {
                if (!$value) return null;
                if (str_starts_with($value, 'http')) return $value;
                if (str_starts_with($value, 'data:image')) return $value;
                
                return Storage::disk('public')->url($value);
            },
        );
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function purpose(): BelongsTo
    {
        return $this->belongsTo(VisitorPurpose::class, 'purpose_id');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function feedback(): HasOne
    {
        return $this->hasOne(VisitorFeedback::class);
    }
}
