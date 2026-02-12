<?php

namespace App\Http\Middleware;

use App\Enums\PositionRolePermission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PositionPermissionCheck
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
            'position.index' => [PositionRolePermission::VIEW_POSITION->value, PositionRolePermission::MANAGE_POSITION->value],
            'position.datatable' => [PositionRolePermission::VIEW_POSITION->value, PositionRolePermission::MANAGE_POSITION->value],
            'position.print' => [PositionRolePermission::VIEW_POSITION->value, PositionRolePermission::MANAGE_POSITION->value],
            'position.create' => [PositionRolePermission::MANAGE_POSITION->value],
            'position.store' => [PositionRolePermission::MANAGE_POSITION->value],
            'position.edit' => [PositionRolePermission::MANAGE_POSITION->value],
            'position.update' => [PositionRolePermission::MANAGE_POSITION->value],
            'position.delete' => [PositionRolePermission::MANAGE_POSITION->value],
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
