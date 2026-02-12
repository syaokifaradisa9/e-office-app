<?php

namespace App\Http\Middleware;

use App\Enums\DivisionRolePermission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DivisionPermissionCheck
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
            'division.index' => [DivisionRolePermission::VIEW_DIVISION->value, DivisionRolePermission::MANAGE_DIVISION->value],
            'division.datatable' => [DivisionRolePermission::VIEW_DIVISION->value, DivisionRolePermission::MANAGE_DIVISION->value],
            'division.print' => [DivisionRolePermission::VIEW_DIVISION->value, DivisionRolePermission::MANAGE_DIVISION->value],
            'division.create' => [DivisionRolePermission::MANAGE_DIVISION->value],
            'division.store' => [DivisionRolePermission::MANAGE_DIVISION->value],
            'division.edit' => [DivisionRolePermission::MANAGE_DIVISION->value],
            'division.update' => [DivisionRolePermission::MANAGE_DIVISION->value],
            'division.delete' => [DivisionRolePermission::MANAGE_DIVISION->value],
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
