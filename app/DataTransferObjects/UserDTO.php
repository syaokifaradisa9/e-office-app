<?php

namespace App\DataTransferObjects;

use App\Http\Requests\UserRequest;

class UserDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password = null,
        public ?int $divisionId = null,
        public ?int $positionId = null,
        public ?int $roleId = null,
        public ?string $phone = null,
        public ?string $address = null,
        public ?string $profilePhoto = null,
        public bool $isActive = true,
    ) {}

    public static function fromAppRequest(UserRequest $request): self
    {
        $data = $request->validated();

        return new self(
            name: $data['name'],
            email: $data['email'],
            password: $data['password'] ?? null,
            divisionId: $data['division_id'] ?? null,
            positionId: $data['position_id'] ?? null,
            roleId: $data['role_id'] ?? null,
            phone: $data['phone'] ?? null,
            address: $data['address'] ?? null,
            profilePhoto: $data['profile_photo'] ?? null,
            isActive: $data['is_active'] ?? true,
        );
    }

    public function toModelPayload(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'division_id' => $this->divisionId,
            'position_id' => $this->positionId,
            'phone' => $this->phone,
            'address' => $this->address,
            'profile_photo' => $this->profilePhoto,
            'is_active' => $this->isActive,
        ];
    }
}
