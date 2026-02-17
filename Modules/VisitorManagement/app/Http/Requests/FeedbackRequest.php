<?php

namespace Modules\VisitorManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public page
    }

    public function rules(): array
    {
        return [
            'feedback_note' => 'nullable|string',
            'ratings' => 'required|array',
            'ratings.*' => 'required|integer|min:1|max:5',
        ];
    }
}
