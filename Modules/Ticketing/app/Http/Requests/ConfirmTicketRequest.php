<?php

namespace Modules\Ticketing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Ticketing\Enums\TicketPriority;

class ConfirmTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $priorities = implode(',', TicketPriority::values());

        return [
            'action' => 'required|in:accept,reject',
            'note' => 'nullable|string',
            'real_priority' => "required_if:action,accept|nullable|in:{$priorities}",
            'priority_reason' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'Aksi wajib dipilih.',
            'action.in' => 'Aksi tidak valid.',
            'real_priority.required_if' => 'Priority aktual wajib dipilih jika menerima tiket.',
        ];
    }
}
