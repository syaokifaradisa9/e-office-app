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
            'division_id' => 'nullable|integer|exists:divisions,id',
            'notes' => 'nullable|string|max:500',
        ];
    }
}
