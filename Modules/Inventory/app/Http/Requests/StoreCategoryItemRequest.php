<?php

namespace Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:category_items,name'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama kategori wajib diisi!',
            'name.string' => 'Nama kategori harus berupa teks!',
            'name.max' => 'Nama kategori maksimal 255 karakter!',
            'description.string' => 'Deskripsi harus berupa teks!',
            'description.max' => 'Deskripsi maksimal 500 karakter!',
        ];
    }
}
