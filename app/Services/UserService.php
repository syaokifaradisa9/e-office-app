<?php

namespace App\Services;

use App\DataTransferObjects\UserDTO;
use App\Models\User;
use App\Repositories\User\UserRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class UserService
{
    private const CACHE_KEY = 'users_all';

    public function __construct(private UserRepository $userRepository) {}

    public function getAll(): Collection
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return $this->userRepository->all();
        });
    }

    public function getActive(): Collection
    {
        return $this->userRepository->findBy(['is_active' => true]);
    }

    public function getByDivision(int $divisionId): Collection
    {
        return $this->userRepository->getByDivision($divisionId);
    }

    public function getByDivisions(array $divisionIds): Collection
    {
        return $this->userRepository->getByDivisions($divisionIds);
    }

    public function store(UserDTO $dto): User
    {
        $payload = $dto->toModelPayload();

        if (! empty($payload['password'])) {
            $payload['password'] = Hash::make($payload['password']);
        }

        $user = $this->userRepository->create($payload);

        // Assign role if provided
        if ($dto->roleId) {
            $user->syncRoles([$dto->roleId]);
        }

        $this->clearCache();

        return $user;
    }

    public function update(User $user, UserDTO $dto): User
    {
        $payload = $dto->toModelPayload();

        // Only hash password if provided
        if (! empty($payload['password'])) {
            $payload['password'] = Hash::make($payload['password']);
        } else {
            unset($payload['password']);
        }

        $updated = $this->userRepository->update($user, $payload);

        // Sync role if provided
        if ($dto->roleId) {
            $updated->syncRoles([$dto->roleId]);
        }

        $this->clearCache();

        return $updated;
    }

    public function delete(User $user): bool
    {
        $result = $this->userRepository->delete($user);
        $this->clearCache();

        return $result;
    }

    public function updateProfile(User $user, array $data): void
    {
        // Only allow specific fields to be updated in profile
        $allowedFields = ['name', 'email', 'phone', 'address'];
        $filteredData = array_intersect_key($data, array_flip($allowedFields));

        $this->userRepository->update($user, $filteredData);
        $this->clearCache();
    }

    public function updatePassword(User $user, string $newPassword): void
    {
        $this->userRepository->update($user, ['password' => Hash::make($newPassword)]);
        $this->clearCache();
    }

    private function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
