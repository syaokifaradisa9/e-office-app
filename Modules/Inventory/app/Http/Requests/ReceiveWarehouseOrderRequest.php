<?php

namespace Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReceiveWarehouseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'receipt_date' => 'required|date',
            'receipt_images' => 'required|array|min:1',
            'receipt_images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }
}
