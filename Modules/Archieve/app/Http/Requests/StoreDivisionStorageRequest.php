<?php

namespace Modules\Archieve\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Archieve\Enums\ArchievePermission;

class StoreDivisionStorageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(ArchievePermission::ManageDivisionStorage->value);
    }

    public function rules(): array
    {
        return [
            'division_id' => ['required', 'exists:divisions,id'],
            'max_size_gb' => ['required', 'numeric', 'min:0.1', 'max:10000'],
        ];
    }

    public function messages(): array
    {
        return [
            'max_size_gb.min' => 'Ukuran minimal adalah 0.1 GB',
            'max_size_gb.max' => 'Ukuran maksimal adalah 10000 GB (10 TB)',
        ];
    }
}
