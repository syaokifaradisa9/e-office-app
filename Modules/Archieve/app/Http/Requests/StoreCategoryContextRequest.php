<?php

namespace Modules\Archieve\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Archieve\Enums\ArchieveUserPermission;

class StoreCategoryContextRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(ArchieveUserPermission::ManageCategory->value);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ];
    }
}
