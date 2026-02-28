<?php

namespace Modules\Ticketing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'diagnose' => 'required|string',
            'follow_up' => 'required|string',
            'processed_at' => 'required|date',
            'note' => 'nullable|string',
            'process_attachments' => 'nullable|array',
            'process_attachments.*' => 'file|max:5120',
            'deleted_attachments' => 'nullable|array',
            'deleted_attachments.*' => 'string',
            'needs_further_repair' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'diagnose.required' => 'Diagnosa masalah wajib diisi.',
            'follow_up.required' => 'Tindakan wajib diisi.',
            'processed_at.required' => 'Tanggal penanganan wajib diisi.',
            'process_attachments.*.max' => 'Ukuran maksimal setiap file adalah 5MB.',
        ];
    }
}
