<?php

namespace Modules\VisitorManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VisitorFeedback extends Model
{
    use HasFactory;

    protected $table = 'visitor_feedbacks';

    protected $fillable = [
        'visitor_id',
        'feedback_note',
    ];

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(VisitorFeedbackRating::class, 'visitor_feedback_id');
    }
}
