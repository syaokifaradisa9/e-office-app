<?php

namespace Modules\Ticketing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TicketFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating' => 'required|integer|min:1|max:5',
            'feedback_description' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => 'Rating wajib diisi.',
            'rating.min' => 'Rating minimal 1 bintang.',
            'rating.max' => 'Rating maksimal 5 bintang.',
        ];
    }
}
