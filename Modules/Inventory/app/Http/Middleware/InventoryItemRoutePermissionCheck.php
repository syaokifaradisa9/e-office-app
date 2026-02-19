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
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }

        $routeName = $request->route()->getName();

        $hasPermission = match (true) {
            // Dashboard
            str_contains($routeName, 'inventory.dashboard.index') => true, // base dashboard redirection usually handled in controller
            str_contains($routeName, 'inventory.dashboard.main-warehouse') => $user->can(InventoryPermission::ViewMainWarehouseDashboard->value),
            str_contains($routeName, 'inventory.dashboard.division-warehouse') => $user->can(InventoryPermission::ViewDivisionWarehouseDashboard->value) || $user->can(InventoryPermission::ViewAllWarehouseDashboard->value),

            // Category Item
            str_contains($routeName, 'inventory.categories.index') => $user->can(InventoryPermission::ViewCategory->value) || $user->can(InventoryPermission::ManageCategory->value),
            str_contains($routeName, 'inventory.categories.datatable'),
            str_contains($routeName, 'inventory.categories.print-excel') => $user->can(InventoryPermission::ViewCategory->value),
            str_contains($routeName, 'inventory.categories.create'),
            str_contains($routeName, 'inventory.categories.store'),
            str_contains($routeName, 'inventory.categories.edit'),
            str_contains($routeName, 'inventory.categories.update'),
            str_contains($routeName, 'inventory.categories.delete') => $user->can(InventoryPermission::ManageCategory->value),

            // Item (Gudang Utama)
            str_contains($routeName, 'inventory.items.index'),
            str_contains($routeName, 'inventory.items.datatable'),
            str_contains($routeName, 'inventory.items.print-excel') => $user->can(InventoryPermission::ViewItem->value) || $user->can(InventoryPermission::ManageItem->value),
            str_contains($routeName, 'inventory.items.create'),
            str_contains($routeName, 'inventory.items.store'),
            str_contains($routeName, 'inventory.items.edit'),
            str_contains($routeName, 'inventory.items.update'),
            str_contains($routeName, 'inventory.items.delete') => $user->can(InventoryPermission::ManageItem->value),
            str_contains($routeName, 'inventory.items.convert'),
            str_contains($routeName, 'inventory.items.process-conversion') => $user->can(InventoryPermission::ConvertItemGudang->value),
            str_contains($routeName, 'inventory.items.issue') => $user->can(InventoryPermission::IssueItemGudang->value),

            // Warehouse Order
            str_contains($routeName, 'inventory.warehouse-orders.create'),
            str_contains($routeName, 'inventory.warehouse-orders.store'),
            str_contains($routeName, 'inventory.warehouse-orders.edit'),
            str_contains($routeName, 'inventory.warehouse-orders.update'),
            str_contains($routeName, 'inventory.warehouse-orders.delete') => $user->can(InventoryPermission::CreateWarehouseOrder->value),
            str_contains($routeName, 'inventory.warehouse-orders.datatable'),
            str_contains($routeName, 'inventory.warehouse-orders.print-excel') => $user->can(InventoryPermission::ViewWarehouseOrderDivisi->value) || $user->can(InventoryPermission::ViewAllWarehouseOrder->value),
            str_contains($routeName, 'inventory.warehouse-orders.index'),
            str_contains($routeName, 'inventory.warehouse-orders.show') => 
                $user->can(InventoryPermission::ViewWarehouseOrderDivisi->value) || 
                $user->can(InventoryPermission::ViewAllWarehouseOrder->value) || 
                $user->can(InventoryPermission::CreateWarehouseOrder->value) || 
                $user->can(InventoryPermission::ConfirmWarehouseOrder->value) || 
                $user->can(InventoryPermission::HandoverItem->value) || 
                $user->can(InventoryPermission::ReceiveItem->value),
            str_contains($routeName, 'inventory.warehouse-orders.confirm'),
            str_contains($routeName, 'inventory.warehouse-orders.reject') => $user->can(InventoryPermission::ConfirmWarehouseOrder->value),
            str_contains($routeName, 'inventory.warehouse-orders.delivery'),
            str_contains($routeName, 'inventory.warehouse-orders.deliver') => $user->can(InventoryPermission::HandoverItem->value),
            str_contains($routeName, 'inventory.warehouse-orders.received'),
            str_contains($routeName, 'inventory.warehouse-orders.receive') => $user->can(InventoryPermission::ReceiveItem->value),

            // Stock Opname
            str_contains($routeName, 'inventory.stock-opname.datatable'),
            str_contains($routeName, 'inventory.stock-opname.print-excel'),
            str_contains($routeName, 'inventory.stock-opname.show'),
            str_contains($routeName, 'inventory.stock-opname.detail'),
            str_contains($routeName, 'inventory.stock-opname.index') => 
                $user->can(InventoryPermission::ViewWarehouseStockOpname->value) || 
                $user->can(InventoryPermission::ViewDivisionStockOpname->value) || 
                $user->can(InventoryPermission::ViewAllStockOpname->value),
            str_contains($routeName, 'inventory.stock-opname.create'),
            str_contains($routeName, 'inventory.stock-opname.store'),
            str_contains($routeName, 'inventory.stock-opname.edit'),
            str_contains($routeName, 'inventory.stock-opname.update'),
            str_contains($routeName, 'inventory.stock-opname.delete') => $user->can(InventoryPermission::CreateStockOpname->value),
            str_contains($routeName, 'inventory.stock-opname.process'),
            str_contains($routeName, 'inventory.stock-opname.store-process') => $user->can(InventoryPermission::ProcessStockOpname->value),
            str_contains($routeName, 'inventory.stock-opname.finalize'),
            str_contains($routeName, 'inventory.stock-opname.store-finalize') => $user->can(InventoryPermission::FinalizeStockOpname->value),

            // Item Transactions
            str_contains($routeName, 'inventory.transactions.index'),
            str_contains($routeName, 'inventory.transactions.datatable'),
            str_contains($routeName, 'inventory.transactions.print-excel') => $user->can(InventoryPermission::MonitorItemTransaction->value) || $user->can(InventoryPermission::MonitorAllItemTransaction->value),

            // Stock Monitoring
            str_contains($routeName, 'inventory.stock-monitoring.index'),
            str_contains($routeName, 'inventory.stock-monitoring.datatable'),
            str_contains($routeName, 'inventory.stock-monitoring.print-excel') => $user->can(InventoryPermission::MonitorStock->value) || $user->can(InventoryPermission::MonitorAllStock->value),
            str_contains($routeName, 'inventory.stock-monitoring.convert'),
            str_contains($routeName, 'inventory.stock-monitoring.process-conversion') => $user->can(InventoryPermission::ConvertStock->value),
            str_contains($routeName, 'inventory.stock-monitoring.issue') => $user->can(InventoryPermission::IssueStock->value),

            // Reports
            str_contains($routeName, 'inventory.reports.division') => $user->can(InventoryPermission::ViewDivisionReport->value) || $user->can(InventoryPermission::ViewAllReport->value),
            str_contains($routeName, 'inventory.reports.all') => $user->can(InventoryPermission::ViewAllReport->value),
            str_contains($routeName, 'inventory.reports.print-excel') => $user->can(InventoryPermission::ViewDivisionReport->value) || $user->can(InventoryPermission::ViewAllReport->value),

            default => true,
        };

        if (!$hasPermission) {
            abort(403);
        }

        return $next($request);
    }
}
