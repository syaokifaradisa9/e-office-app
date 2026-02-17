<?php

namespace Modules\Archieve\DataTransferObjects;

use Illuminate\Http\Request;

class StoreDocumentClassificationDTO
{
    public function __construct(
        public readonly string $code,
        public readonly string $name,
        public readonly ?int $parent_id = null,
        public readonly ?string $description = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            code: $request->validated('code'),
            name: $request->validated('name'),
            parent_id: $request->validated('parent_id') ? (int) $request->validated('parent_id') : null,
            description: $request->validated('description'),
        );
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'parent_id' => $this->parent_id,
            'description' => $this->description,
        ];
    }
}
