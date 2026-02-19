<?php

namespace Modules\Inventory\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Inventory\Services\StockOpnameService;
use Symfony\Component\HttpFoundation\Response;

class CheckActiveStockOpname
{
    public function __construct(
        private StockOpnameService $stockOpnameService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // 0. Only check for users who have some Inventory-related permission
        // This keeps it dynamic: a user with only Archieve access won't trigger this logic.
        if ($user) {
            $hasInventoryPermission = $user->getAllPermissions()->contains(function ($permission) {
                $name = $permission->name;
                return str_contains($name, 'Gudang') || 
                       str_contains($name, 'Barang') || 
                       str_contains($name, 'Stok') || 
                       str_contains($name, 'Stock Opname');
            });

            if (!$hasInventoryPermission) {
                return $next($request);
            }
        }

        // 1. Safety check for database table
        if (!\Illuminate\Support\Facades\Schema::hasTable('stock_opnames')) {
            return $next($request);
        }

        // 2. Check status via Service
        $isPending = $this->stockOpnameService->isMenuHidden($user?->division_id);

        // 3. Share with Inertia dynamically
        \Inertia\Inertia::share('is_stock_opname_pending', $isPending);

        // 4. Block access if necessary (Original logic)
        if ($isPending) {
            $message = 'Akses ditangguhkan sementara karena sedang ada proses Stock Opname yang aktif di area Anda.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 403);
            }

            return back()->with('error', $message);
        }

        return $next($request);
    }
}
