<?php

namespace Modules\VisitorManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\VisitorManagement\Enums\VisitorUserPermission;

class CreateInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(VisitorUserPermission::CreateInvitation->value);
    }

    public function rules(): array
    {
        return [
            'visitor_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'organization' => 'required|string|max:255',
            'division_id' => 'required|exists:divisions,id',
            'purpose_id' => 'required|exists:visitor_purposes,id',
            'purpose_detail' => 'nullable|string',
            'visitor_count' => 'required|integer|min:1',
        ];
    }
}
