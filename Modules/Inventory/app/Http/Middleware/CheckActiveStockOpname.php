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

        if (!$user) {
            return $next($request);
        }

        // 1. Safety check for database table
        if (!\Illuminate\Support\Facades\Schema::hasTable('stock_opnames')) {
            return $next($request);
        }

        // 2. Check status via Service
        $isPending = $this->stockOpnameService->isMenuHidden($user?->division_id);

        // 3. Share with Inertia dynamically for Sidebar
        \Inertia\Inertia::share('is_stock_opname_pending', $isPending);

        // 4. Block access ONLY for specific transactional routes
        if ($isPending) {
            $routeName = $request->route()->getName();
            
            $routesToBlock = [
                'inventory.items.',
                'inventory.stock-monitoring.',
                'inventory.warehouse-orders.',
            ];

            $shouldBlock = false;
            foreach ($routesToBlock as $routePrefix) {
                if (str_starts_with($routeName, $routePrefix)) {
                    $shouldBlock = true;
                    break;
                }
            }

            if ($shouldBlock) {
                $message = 'Akses ditangguhkan sementara karena sedang ada proses Stock Opname yang aktif di area Anda.';

                if ($request->expectsJson()) {
                    return response()->json(['message' => $message], 403);
                }

                abort(403, $message);
            }
        }

        return $next($request);
    }
}
