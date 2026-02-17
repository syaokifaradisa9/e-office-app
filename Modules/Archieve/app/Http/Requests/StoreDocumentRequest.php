<?php

namespace Modules\Archieve\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Archieve\Enums\ArchieveUserPermission;

class StoreDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if ($this->isMethod('POST')) {
            return $this->user()->can(ArchieveUserPermission::ManageAll->value) || 
                   $this->user()->can(ArchieveUserPermission::ManageDivision->value);
        }

        return $this->user()->can(ArchieveUserPermission::ManageAll->value) || 
               $this->user()->can(ArchieveUserPermission::ManageDivision->value);
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $user = $this->user();
        if (!$user->can(ArchieveUserPermission::ManageAll->value) && 
            $user->can(ArchieveUserPermission::ManageDivision->value)) {
            
            // Force division_ids to be only the user's division
            if ($user->division_id) {
                $this->merge([
                    'division_ids' => [$user->division_id]
                ]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'classification_id' => 'required|exists:archieve_document_classifications,id',
            'category_ids' => 'required|array',
            'category_ids.*' => 'exists:archieve_categories,id',
            'division_ids' => 'required|array',
            'division_ids.*' => 'exists:divisions,id',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
        ];

        if ($this->isMethod('POST')) {
            $rules['file'] = 'required|file|max:51200'; // 50MB
        } else {
            $rules['file'] = 'nullable|file|max:51200';
        }

        return $rules;
    }
}
