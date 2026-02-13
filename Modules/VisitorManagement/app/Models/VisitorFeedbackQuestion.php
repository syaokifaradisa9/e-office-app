<?php

namespace Modules\VisitorManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VisitorFeedbackQuestion extends Model
{
    use HasFactory;
    
    protected static function newFactory(): \Modules\VisitorManagement\Database\Factories\VisitorFeedbackQuestionFactory
    {
        return \Modules\VisitorManagement\Database\Factories\VisitorFeedbackQuestionFactory::new();
    }

    protected $fillable = [
        'question',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function ratings(): HasMany
    {
        return $this->hasMany(VisitorFeedbackRating::class, 'question_id');
    }
}

