<?php

namespace Modules\VisitorManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Modules\VisitorManagement\Database\Factories\VisitorPurposeFactory;

class VisitorPurpose extends Model
{
    use HasFactory;

    protected $table = 'visitor_purposes';

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): VisitorPurposeFactory
    {
        return VisitorPurposeFactory::new();
    }

    public function visitors(): HasMany
    {
        return $this->hasMany(Visitor::class, 'purpose_id');
    }
}
