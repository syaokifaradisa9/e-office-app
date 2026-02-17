<?php

namespace Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockOpnameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'opname_date' => 'required|date',
            'division_id' => 'required', // Can be 'warehouse' or integer ID
            'notes' => 'nullable|string|max:500',
        ];
    }
}
