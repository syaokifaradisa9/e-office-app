<?php

namespace App\Http\Middleware;

use App\Enums\UserRolePermission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserPermissionCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()->getName();

        $permissions = [
            'user.index' => [UserRolePermission::VIEW_USER->value, UserRolePermission::MANAGE_USER->value],
            'user.datatable' => [UserRolePermission::VIEW_USER->value, UserRolePermission::MANAGE_USER->value],
            'user.print' => [UserRolePermission::VIEW_USER->value, UserRolePermission::MANAGE_USER->value],
            'user.create' => [UserRolePermission::MANAGE_USER->value],
            'user.store' => [UserRolePermission::MANAGE_USER->value],
            'user.edit' => [UserRolePermission::MANAGE_USER->value],
            'user.update' => [UserRolePermission::MANAGE_USER->value],
            'user.delete' => [UserRolePermission::MANAGE_USER->value],
        ];

        if (isset($permissions[$routeName])) {
            $hasPermission = false;
            foreach ($permissions[$routeName] as $permission) {
                if ($request->user()?->can($permission)) {
                    $hasPermission = true;
                    break;
                }
            }

            if (!$hasPermission) {
                abort(403, 'Anda tidak memiliki hak akses untuk halaman ini.');
            }
        }

        return $next($request);
    }
}
