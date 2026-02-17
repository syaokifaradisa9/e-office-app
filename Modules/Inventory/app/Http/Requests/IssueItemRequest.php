<?php

namespace Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IssueItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $item = $this->route('item');

        return [
            'quantity' => [
                'required',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) use ($item) {
                    if ($item && $value > $item->stock) {
                        $fail('Jumlah pengeluaran tidak boleh melebihi stok yang tersedia ('.$item->stock.').');
                    }
                },
            ],
            'description' => 'required|string|max:500',
        ];
    }
}
