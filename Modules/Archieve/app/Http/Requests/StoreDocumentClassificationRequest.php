<?php

namespace Modules\Archieve\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Archieve\Enums\ArchievePermission;

class StoreDocumentClassificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(ArchievePermission::ManageClassification->value);
    }

    public function rules(): array
    {
        $id = $this->classification?->id ?? 'NULL';
        
        return [
            'code' => ['required', 'string', 'max:50', 'unique:archieve_document_classifications,code,' . $id],
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:archieve_document_classifications,id'],
            'description' => ['nullable', 'string'],
        ];
    }
}
