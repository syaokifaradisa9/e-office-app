<?php

namespace Modules\Archieve\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Archieve\Enums\ArchieveUserPermission;

class StoreDocumentClassificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(ArchieveUserPermission::ManageClassification->value);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => 'required|string|max:255|unique:archieve_document_classifications,code,' . $this->route('classification')?->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:archieve_document_classifications,id',
        ];
    }
}
