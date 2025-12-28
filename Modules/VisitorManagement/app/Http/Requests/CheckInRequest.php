<?php

namespace Modules\VisitorManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public page
    }

    public function rules(): array
    {
        return [
            'visitor_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'organization' => 'required|string|max:255',
            'photo_url' => 'required|string', // Base64 or URL
            'division_id' => 'required|exists:divisions,id',
            'purpose_id' => 'required|exists:visitor_purposes,id',
            'purpose_detail' => 'required|string',
            'visitor_count' => 'required|integer|min:1',
            'invited_id' => 'nullable|exists:visitors,id',
        ];
    }
}
