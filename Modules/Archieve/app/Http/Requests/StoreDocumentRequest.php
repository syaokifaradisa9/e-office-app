<?php

namespace Modules\Archieve\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Archieve\Enums\ArchievePermission;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('kelola_arsip_divisi')
            || $this->user()->can('kelola_semua_arsip');
    }

    public function rules(): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'classification_id' => ['required', 'exists:archieve_document_classifications,id'],
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['exists:archieve_categories,id'],
            'division_ids' => ['required', 'array', 'min:1'],
            'division_ids.*' => ['exists:divisions,id'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['exists:users,id'],
        ];

        // File required only on create
        if ($this->isMethod('POST')) {
            $rules['file'] = ['required', 'file', 'max:102400']; // 100MB max
        } else {
            $rules['file'] = ['nullable', 'file', 'max:102400'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'category_ids.required' => 'Pilih minimal satu kategori untuk setiap konteks.',
            'category_ids.min' => 'Pilih minimal satu kategori.',
            'division_ids.required' => 'Pilih minimal satu divisi.',
            'division_ids.min' => 'Pilih minimal satu divisi.',
            'file.required' => 'File dokumen wajib diupload.',
            'file.max' => 'Ukuran file maksimal 100MB.',
        ];
    }
}
