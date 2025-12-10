<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Auth\LoginService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function __construct(
        protected LoginService $loginService
    ) {}

    public function index(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function verify(LoginRequest $request): RedirectResponse
    {
        $this->loginService->login($request->validated());

        return redirect()->intended(route('dashboard.index'));
    }

    public function logout(): RedirectResponse
    {
        $this->loginService->logout();

        return redirect()->route('auth.login');
    }
}
