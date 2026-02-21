<?php

namespace Modules\Ticketing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Modules\Ticketing\Enums\AssetCategoryType;

class StoreAssetCategoryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => ['required', new Enum(AssetCategoryType::class)],
            'division_id' => 'nullable|exists:divisions,id',
            'maintenance_count' => 'required|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama kategori aset wajib diisi.',
            'name.string' => 'Nama kategori aset harus berupa teks.',
            'name.max' => 'Nama kategori aset tidak boleh lebih dari 255 karakter.',
            'type.required' => 'Tipe aset wajib dipilih.',
            'type.Illuminate\Validation\Rules\Enum' => 'Tipe aset yang dipilih tidak valid.',
            'division_id.exists' => 'Divisi yang dipilih tidak terdaftar di sistem.',
            'maintenance_count.required' => 'Jumlah maintenance wajib diisi.',
            'maintenance_count.integer' => 'Jumlah maintenance harus berupa angka.',
            'maintenance_count.min' => 'Jumlah maintenance minimal 0.',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
