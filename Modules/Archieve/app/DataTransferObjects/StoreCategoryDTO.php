<?php

namespace Modules\Archieve\DataTransferObjects;

use Illuminate\Http\Request;

class StoreCategoryDTO
{
    public function __construct(
        public readonly string $name,
        public readonly int $context_id,
        public readonly ?string $description = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->validated('name'),
            context_id: (int) $request->validated('context_id'),
            description: $request->validated('description'),
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'context_id' => $this->context_id,
            'description' => $this->description,
        ];
    }
}
