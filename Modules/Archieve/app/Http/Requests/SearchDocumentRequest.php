<?php

namespace Modules\Archieve\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Archieve\Enums\ArchieveUserPermission;

class SearchDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        return $user->can(ArchieveUserPermission::SearchAllScope->value) ||
               $user->can(ArchieveUserPermission::SearchDivisionScope->value) ||
               $user->can(ArchieveUserPermission::SearchPersonalScope->value);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'classification_id' => 'nullable|exists:archieve_document_classifications,id',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:archieve_categories,id',
            'division_ids' => 'nullable|array',
            'division_ids.*' => 'exists:divisions,id',
            'user_name' => 'nullable|string',
            'search' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}
