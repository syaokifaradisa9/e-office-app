<?php

namespace Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessStockOpnameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.physical_stock' => 'nullable|integer|min:0',
            'items.*.notes' => 'nullable|string',
            'confirm' => 'nullable|boolean',
        ];
    }
}
