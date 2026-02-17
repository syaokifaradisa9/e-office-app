<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Services\UserService;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function __construct(private UserService $userService) {}

    public function editProfile()
    {
        return Inertia::render('Profile/Edit');
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $this->userService->updateProfile($request->user(), $request->validated());

        return redirect()->back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function editPassword()
    {
        return Inertia::render('Profile/Password');
    }

    public function updatePassword(UpdatePasswordRequest $request)
    {
        $this->userService->updatePassword($request->user(), $request->validated()['password']);

        return redirect()->back()->with('success', 'Kata sandi berhasil diperbarui.');
    }
}
