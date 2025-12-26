<?php

namespace Modules\Archieve\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Archieve\Enums\ArchievePermission;

class StoreCategoryContextRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(ArchievePermission::ManageCategory->value);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:archieve_category_contexts,name,' . ($this->context?->id ?? 'NULL')],
            'description' => ['nullable', 'string'],
        ];
    }
}
