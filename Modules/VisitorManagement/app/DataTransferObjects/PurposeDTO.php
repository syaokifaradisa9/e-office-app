<?php

namespace Modules\VisitorManagement\DataTransferObjects;

use Illuminate\Http\Request;

class PurposeDTO
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description,
        public readonly bool $is_active = true
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->input('name'),
            description: $request->input('description'),
            is_active: $request->boolean('is_active', true)
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
        ];
    }
}
