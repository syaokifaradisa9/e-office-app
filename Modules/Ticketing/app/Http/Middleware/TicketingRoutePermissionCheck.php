<?php

namespace Modules\Ticketing\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Ticketing\Enums\TicketingPermission;
use Symfony\Component\HttpFoundation\Response;

class TicketingRoutePermissionCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }

        $routeName = $request->route()->getName();

        // If it's the main ticketing entry point, we can allow it if they have any ticketing permission
        if ($routeName === 'ticketing.index') {
            return $next($request);
        }

        $hasPermission = match (true) {
            // Asset Model Management
            str_contains($routeName, 'ticketing.asset-models.index'),
            str_contains($routeName, 'ticketing.asset-models.datatable'),
            str_contains($routeName, 'ticketing.asset-models.print-excel') => 
                $user->can(TicketingPermission::ViewAssetModelDivisi) ||
                $user->can(TicketingPermission::ViewAllAssetModel) ||
                $user->can(TicketingPermission::ManageAssetModel),

            str_contains($routeName, 'ticketing.asset-models.create'),
            str_contains($routeName, 'ticketing.asset-models.store'),
            str_contains($routeName, 'ticketing.asset-models.edit'),
            str_contains($routeName, 'ticketing.asset-models.update') => 
                $user->can(TicketingPermission::ManageAssetModel),

            str_contains($routeName, 'ticketing.asset-models.delete') => 
                $user->can(TicketingPermission::DeleteAssetModel),

            default => true,
        };

        if (!$hasPermission) {
            abort(403);
        }

        return $next($request);
    }
}
