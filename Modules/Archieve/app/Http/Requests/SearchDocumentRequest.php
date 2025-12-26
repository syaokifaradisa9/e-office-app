<?php

namespace Modules\Archieve\Http\Requests;

use App\Http\Requests\DatatableRequest;

class SearchDocumentRequest extends DatatableRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'classification_id' => 'nullable|integer|exists:archieve_document_classifications,id',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:archieve_categories,id',
            'division_ids' => 'nullable|array',
            'division_ids.*' => 'integer|exists:divisions,id',
            'user_name' => 'nullable|string|max:255',
            'search' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);
    }

    public function authorize(): bool
    {
        return auth()->user()->can('pencarian_dokumen_keseluruhan') || 
               auth()->user()->can('pencarian_dokumen_divisi') || 
               auth()->user()->can('pencarian_dokumen_pribadi');
    }
}
