<?php

namespace Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:category_items,id',
            'unit_of_measure' => 'required|string|max:50',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string|max:500',
            'multiplier' => 'nullable|integer|min:1',
            'reference_item_id' => 'nullable|exists:items,id',
        ];
    }
}
