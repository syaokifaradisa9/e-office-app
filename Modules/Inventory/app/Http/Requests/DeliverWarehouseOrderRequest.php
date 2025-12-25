<?php

namespace Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeliverWarehouseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'delivery_date' => 'required|date',
            'delivery_images' => 'required|array|min:1',
            'delivery_images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }
}
