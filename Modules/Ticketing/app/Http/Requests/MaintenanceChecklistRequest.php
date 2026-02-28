<?php

namespace Modules\Ticketing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MaintenanceChecklistRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'actual_date' => 'required|date',
            'note' => 'nullable|string',
            'needs_further_repair' => 'nullable|boolean',
            'checklists' => 'required|array',
            'checklists.*.checklist_id' => 'required|exists:checklists,id',
            'checklists.*.label' => 'required|string',
            'checklists.*.description' => 'nullable|string',
            'checklists.*.value' => 'required|string|in:Baik,Tidak Baik',
            'checklists.*.note' => 'nullable|string',
            'checklists.*.follow_up' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'attachments.*.max' => 'Ukuran maksimal setiap file adalah 5MB.',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
