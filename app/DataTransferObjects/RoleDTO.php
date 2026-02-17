<?php

namespace App\DataTransferObjects;

use App\Http\Requests\RoleRequest;

class RoleDTO
{
    public function __construct(
        public string $name,
        public array $permissions = [],
    ) {}

    public static function fromAppRequest(RoleRequest $request): self
    {
        $data = $request->validated();

        return new self(
            name: $data['name'],
            permissions: $data['permissions'] ?? [],
        );
    }

    public function toModelPayload(): array
    {
        return [
            'name' => $this->name,
        ];
    }
}
