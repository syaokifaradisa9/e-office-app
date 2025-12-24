<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        $successMessage = $request->session()->pull('success');
        $errorMessage = $request->session()->pull('error');
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'initials' => $user->initials,
                ] : null,
            ],
            'loggeduser' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'division_id' => $user->division_id,
                'position' => $user->position?->name,
            ] : null,
            'permissions' => $user ? $user->getAllPermissions()->pluck('name')->toArray() : [],
            'flash' => [
                'message' => $successMessage ?? $errorMessage ?? null,
                'type' => $successMessage ? 'success' : ($errorMessage ? 'error' : null),
            ]
        ];
    }
}
