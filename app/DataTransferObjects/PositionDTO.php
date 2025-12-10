<?php

namespace App\DataTransferObjects;

use App\Http\Requests\PositionRequest;

class PositionDTO
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public bool $isActive = true,
    ) {}

    public static function fromAppRequest(PositionRequest $request): self
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
