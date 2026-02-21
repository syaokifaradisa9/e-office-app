<?php

namespace Modules\Ticketing\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Ticketing\Enums\TicketingPermission;
use Modules\Ticketing\Models\AssetCategory;
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
            // Asset Category Management - View
            str_contains($routeName, 'ticketing.asset-categories.index'),
            str_contains($routeName, 'ticketing.asset-categories.datatable'),
            str_contains($routeName, 'ticketing.asset-categories.print-excel') => 
                $user->can(TicketingPermission::ViewAssetCategoryDivisi->value) ||
                $user->can(TicketingPermission::ViewAllAssetCategory->value) ||
                $user->can(TicketingPermission::ManageAssetCategory->value),

            // Asset Category Management - Manage
            str_contains($routeName, 'ticketing.asset-categories.create'),
            str_contains($routeName, 'ticketing.asset-categories.store'),
            str_contains($routeName, 'ticketing.asset-categories.edit'),
            str_contains($routeName, 'ticketing.asset-categories.update') => 
                $user->can(TicketingPermission::ManageAssetCategory->value),

            // Asset Category Management - Delete
            str_contains($routeName, 'ticketing.asset-categories.delete') => 
                $user->can(TicketingPermission::DeleteAssetCategory->value),

            // Checklist Management - View
            str_contains($routeName, 'ticketing.asset-categories.checklists.index'),
            str_contains($routeName, 'ticketing.asset-categories.checklists.datatable'),
            str_contains($routeName, 'ticketing.asset-categories.checklists.print-excel') =>
                $user->can(TicketingPermission::ViewChecklist->value) ||
                $user->can(TicketingPermission::ManageChecklist->value),

            // Checklist Management - Manage
            str_contains($routeName, 'ticketing.asset-categories.checklists.create'),
            str_contains($routeName, 'ticketing.asset-categories.checklists.store'),
            str_contains($routeName, 'ticketing.asset-categories.checklists.edit'),
            str_contains($routeName, 'ticketing.asset-categories.checklists.update') =>
                $user->can(TicketingPermission::ManageChecklist->value),

            // Checklist Management - Delete
            str_contains($routeName, 'ticketing.asset-categories.checklists.delete') =>
                $user->can(TicketingPermission::ManageChecklist->value),

            // Asset Management - View
            str_contains($routeName, 'ticketing.assets.index'),
            str_contains($routeName, 'ticketing.assets.datatable'),
            str_contains($routeName, 'ticketing.assets.print-excel') =>
                $user->can(TicketingPermission::ViewPersonalAsset->value) ||
                $user->can(TicketingPermission::ViewDivisionAsset->value) ||
                $user->can(TicketingPermission::ViewAllAsset->value) ||
                $user->can(TicketingPermission::ManageAsset->value),

            // Asset Management - Manage
            str_contains($routeName, 'ticketing.assets.create'),
            str_contains($routeName, 'ticketing.assets.store'),
            str_contains($routeName, 'ticketing.assets.edit'),
            str_contains($routeName, 'ticketing.assets.update') =>
                $user->can(TicketingPermission::ManageAsset->value),

            // Asset Management - Delete
            str_contains($routeName, 'ticketing.assets.delete') =>
                $user->can(TicketingPermission::DeleteAsset->value),

            // Maintenance Management - View
            str_contains($routeName, 'ticketing.maintenances.index'),
            str_contains($routeName, 'ticketing.maintenances.datatable'),
            str_contains($routeName, 'ticketing.maintenances.print-excel'),
            str_contains($routeName, 'ticketing.maintenances.detail') =>
                $user->can(TicketingPermission::ViewDivisionMaintenance->value) ||
                $user->can(TicketingPermission::ViewAllMaintenance->value) ||
                $user->can(TicketingPermission::ViewPersonalAsset->value) ||
                $user->can(TicketingPermission::ManageAsset->value),

            // Maintenance Management - Manage
            str_contains($routeName, 'ticketing.maintenances.complete') ||
            str_contains($routeName, 'ticketing.maintenances.store-checklist') =>
                $user->can(TicketingPermission::ProsesMaintenance->value),

            str_contains($routeName, 'ticketing.maintenances.confirm') =>
                $user->can(TicketingPermission::ConfirmMaintenance->value),

            str_contains($routeName, 'ticketing.maintenances.cancel') =>
                $user->can(TicketingPermission::ManageAsset->value),

            default => true,
        };

        if (!$hasPermission) {
            abort(403);
        }

        // Division-level access control for checklist routes
        // User dengan ViewAssetCategoryDivisi hanya bisa akses checklist dari kategori divisi sendiri
        if (str_contains($routeName, 'ticketing.asset-categories.checklists.')) {
            $assetCategory = $request->route('assetCategory');

            if ($assetCategory) {
                // Resolve model jbika masih berupa ID
                if (!$assetCategory instanceof AssetCategory) {
                    $assetCategory = AssetCategory::find($assetCategory);
                }

                if ($assetCategory) {
                    $isAll = $user->can(TicketingPermission::ViewAllAssetCategory->value);

                    if (!$isAll) {
                        // User level divisi: hanya bisa akses kategori milik divisi sendiri
                        if ($assetCategory->division_id !== $user->division_id) {
                            abort(403);
                        }
                    }
                }
            }
        }

        return $next($request);
    }
}
