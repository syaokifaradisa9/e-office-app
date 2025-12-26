<?php

namespace Modules\Archieve\DataTransferObjects;

use Illuminate\Http\Request;

class SearchDocumentDTO
{
    public function __construct(
        public ?int $classification_id = null,
        public ?array $category_ids = null,
        public ?array $division_ids = null,
        public ?string $user_name = null,
        public ?string $search = null,
        public ?int $per_page = 15
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            classification_id: $request->filled('classification_id') ? (int) $request->classification_id : null,
            category_ids: $request->filled('category_ids') && is_array($request->category_ids) ? $request->category_ids : null,
            division_ids: $request->filled('division_ids') && is_array($request->division_ids) ? $request->division_ids : null,
            user_name: $request->user_name,
            search: $request->search,
            per_page: $request->filled('per_page') ? (int) $request->per_page : 15
        );
    }
}
