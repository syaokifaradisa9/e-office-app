<?php

namespace Modules\Archieve\DataTransferObjects;

use Illuminate\Http\Request;

class StoreDocumentDTO
{
    public function __construct(
        public readonly string $title,
        public readonly int $classification_id,
        public readonly array $category_ids,
        public readonly array $division_ids,
        public readonly ?string $description = null,
        public readonly ?array $user_ids = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            title: $request->validated('title'),
            classification_id: (int) $request->validated('classification_id'),
            category_ids: $request->validated('category_ids', []),
            division_ids: $request->validated('division_ids', []),
            description: $request->validated('description'),
            user_ids: $request->validated('user_ids'),
        );
    }
}
