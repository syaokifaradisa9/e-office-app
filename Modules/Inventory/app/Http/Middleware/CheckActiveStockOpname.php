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
        
        // isMenuHidden checks if warehouse (null) or user's division has active opname (Pending/Proses)
        if ($this->stockOpnameService->isMenuHidden($user?->division_id)) {
            $message = 'Akses ditangguhkan sementara karena sedang ada proses Stock Opname yang aktif di area Anda.';
            
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 403);
            }

            return back()->with('error', $message);
        }

        return $next($request);
    }
}
