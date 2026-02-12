<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginService
{
    public function login(array $credentials, bool $remember = false): void
    {
        if (! Auth::attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $user = Auth::user();
        if (! $user->is_active) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => 'Akun Anda sedang tidak aktif. Silahkan hubungi administrator.',
            ]);
        }

        session()->regenerate();
    }

    public function logout(): void
    {
        Auth::guard('web')->logout();

        session()->invalidate();

        session()->regenerateToken();
    }
}
