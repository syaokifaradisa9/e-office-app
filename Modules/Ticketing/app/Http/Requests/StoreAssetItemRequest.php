<?php

namespace Modules\Ticketing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssetItemRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'asset_model_id' => 'required|exists:asset_models,id',
            'merk' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => [
                'nullable',
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique('asset_items', 'serial_number')
            ],
            'division_id' => 'required|exists:divisions,id',
            'another_attributes' => 'nullable|array',
            'last_maintenance_date' => 'nullable|date',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'serial_number.unique' => 'S/N ini sudah terdaftar di sistem.',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
