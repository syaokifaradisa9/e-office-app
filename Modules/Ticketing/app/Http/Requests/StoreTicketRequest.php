<?php

namespace Modules\Ticketing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use Modules\Ticketing\Enums\TicketPriority;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $priorities = implode(',', TicketPriority::values());

        return [
            'asset_item_id' => 'required|exists:asset_items,id',
            'subject' => 'required|string|max:255',
            'priority' => "required|in:{$priorities}",
            'priority_reason' => 'required|string',
            'description' => 'required|string',
            'note' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'asset_item_id.required' => 'Asset wajib dipilih.',
            'asset_item_id.exists' => 'Asset tidak ditemukan.',
            'subject.required' => 'Subject masalah wajib diisi.',
            'priority.required' => 'Prioritas wajib dipilih.',
            'priority.in' => 'Prioritas yang dipilih tidak valid.',
            'priority_reason.required' => 'Alasan pemilihan prioritas wajib diisi.',
            'description.required' => 'Deskripsi masalah wajib diisi.',
            'attachments.*.max' => 'Ukuran maksimal setiap file adalah 5MB.',
        ];
    }
}
