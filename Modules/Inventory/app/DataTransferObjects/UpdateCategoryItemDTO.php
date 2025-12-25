<?php

namespace Modules\Inventory\DataTransferObjects;

use Modules\Inventory\Http\Requests\UpdateCategoryItemRequest;

class UpdateCategoryItemDTO
{
    public function __construct(
        public string $name,
        public ?string $description,
        public bool $isActive = true
    ) {}

    public static function fromRequest(UpdateCategoryItemRequest $request): self
    {
        $data = $request->validated();

        return new self(
            name: $data['name'],
            description: $data['description'] ?? null,
            isActive: $data['is_active'] ?? true,
        );
    }

    public function toModelPayload(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->isActive,
        ];
    }
}
