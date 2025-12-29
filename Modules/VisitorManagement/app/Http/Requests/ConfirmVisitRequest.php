<?php

namespace Modules\VisitorManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\VisitorManagement\Enums\VisitorUserPermission;

class ConfirmVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(VisitorUserPermission::ConfirmVisit->value);
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:approved,rejected',
            'admin_note' => 'nullable|string',
        ];
    }
}
