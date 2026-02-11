<?php

namespace Modules\Inventory\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Inventory\Enums\InventoryPermission;
use Symfony\Component\HttpFoundation\Response;

class InventoryItemRoutePermissionCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permissions): Response
    {
        // Permission checking using enum
        // Expected $permissions format: 'ViewItem', 'ManageItem', or 'ViewItem|ManageItem'
        
        $permissionArray = explode('|', $permissions);
        $hasPermission = false;

        foreach ($permissionArray as $permission) {
            try {
                $enumCase = constant(InventoryPermission::class . '::' . trim($permission));
                if ($request->user()?->can($enumCase->value)) {
                    $hasPermission = true;
                    break;
                }
            } catch (\Error $e) {
                // If it's not a valid enum case, we check if it's a raw permission string (for flexibility)
                if ($request->user()?->can($permission)) {
                    $hasPermission = true;
                    break;
                }
            }
        }

        if (!$hasPermission) {
            abort(403);
        }

        return $next($request);
    }
}
