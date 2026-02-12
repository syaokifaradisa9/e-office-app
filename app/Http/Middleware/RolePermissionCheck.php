<?php

namespace App\Http\Middleware;

use App\Enums\RoleRolePermission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RolePermissionCheck
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
            'role.index' => [RoleRolePermission::VIEW_ROLE->value, RoleRolePermission::MANAGE_ROLE->value],
            'role.datatable' => [RoleRolePermission::VIEW_ROLE->value, RoleRolePermission::MANAGE_ROLE->value],
            'role.print' => [RoleRolePermission::VIEW_ROLE->value, RoleRolePermission::MANAGE_ROLE->value],
            'role.create' => [RoleRolePermission::MANAGE_ROLE->value],
            'role.store' => [RoleRolePermission::MANAGE_ROLE->value],
            'role.edit' => [RoleRolePermission::MANAGE_ROLE->value],
            'role.update' => [RoleRolePermission::MANAGE_ROLE->value],
            'role.delete' => [RoleRolePermission::MANAGE_ROLE->value],
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
