<?php

namespace Modules\Ticketing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Modules\Ticketing\Enums\AssetModelType;

class UpdateAssetModelRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => ['required', new Enum(AssetModelType::class)],
            'division_id' => 'required|exists:divisions,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama asset model wajib diisi.',
            'name.string' => 'Nama asset model harus berupa teks.',
            'name.max' => 'Nama asset model tidak boleh lebih dari 255 karakter.',
            'type.required' => 'Tipe asset wajib dipilih.',
            'type.Illuminate\Validation\Rules\Enum' => 'Tipe asset yang dipilih tidak valid.',
            'division_id.required' => 'Divisi wajib dipilih.',
            'division_id.exists' => 'Divisi yang dipilih tidak terdaftar di sistem.',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
