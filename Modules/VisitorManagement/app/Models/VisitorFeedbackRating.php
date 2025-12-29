<?php

namespace Modules\VisitorManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitorFeedbackRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'visitor_feedback_id',
        'question_id',
        'rating',
    ];

    public function feedback(): BelongsTo
    {
        return $this->belongsTo(VisitorFeedback::class, 'visitor_feedback_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(VisitorFeedbackQuestion::class, 'question_id');
    }
}
