<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\DatatableRequest;
use App\Models\Division;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Inventory\Datatables\WarehouseOrderDatatableService;
use Modules\Inventory\Models\Item;
use Modules\Inventory\Models\WarehouseOrder;
use Modules\Inventory\Services\WarehouseOrderService;

class WarehouseOrderController extends Controller
{
    public function __construct(
        private WarehouseOrderService $warehouseOrderService,
        private WarehouseOrderDatatableService $datatableService
    ) {}

    public function index()
    {
        return Inertia::render('Inventory/WarehouseOrder/Index', [
            'users' => \App\Models\User::where('is_active', true)->get(['id', 'name']),
            'divisions' => Division::where('is_active', true)->get(['id', 'name']),
        ]);
    }

    public function create()
    {
        $user = auth()->user();

        if (! $user->division_id) {
            return to_route('inventory.warehouse-orders.index')
                ->with('error', 'Anda tidak memiliki divisi. Hubungi admin untuk mengatur divisi Anda.');
        }

        return Inertia::render('Inventory/WarehouseOrder/Create', [
            'items' => Item::whereNull('division_id')->where('stock', '>', 0)->get(['id', 'name', 'stock', 'unit_of_measure', 'category_id', 'description']),
            'categories' => \Modules\Inventory\Models\CategoryItem::all(['id', 'name']),
            'userDivision' => Division::find($user->division_id, ['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if (! $user->division_id) {
            return back()->withErrors(['division_id' => 'Anda tidak memiliki divisi.']);
        }

        $validated = $request->validate([
            'description' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        // Set division_id from logged user
        $validated['division_id'] = $user->division_id;

        $this->warehouseOrderService->store($validated, $user);

        return to_route('inventory.warehouse-orders.index')
            ->with('success', 'Permintaan barang berhasil dibuat.');
    }

    public function show(WarehouseOrder $warehouseOrder)
    {
        $user = auth()->user();

        if (! $this->warehouseOrderService->canView($warehouseOrder, $user)) {
            abort(403, 'Anda tidak memiliki akses untuk melihat permintaan ini.');
        }

        return Inertia::render('Inventory/WarehouseOrder/Show', [
            'order' => $warehouseOrder->load(['user', 'division', 'carts.item', 'deliveredBy', 'receivedBy', 'latestReject']),
        ]);
    }

    public function edit(WarehouseOrder $warehouseOrder)
    {
        if (! $this->warehouseOrderService->canEdit($warehouseOrder)) {
            abort(403, 'Permintaan barang yang sudah diproses tidak dapat diedit.');
        }

        // Transform carts to include item_id directly for frontend
        $warehouseOrder->load(['carts.item', 'latestReject']);
        $warehouseOrder->carts->transform(function ($cart) {
            $cart->item_id = $cart->item_id ?? $cart->item->id;
            return $cart;
        });

        // Get ordered quantities from current order
        $orderedQuantities = $warehouseOrder->carts->pluck('quantity', 'item_id')->toArray();

        // Check if order is rejected - if so, stock was already returned
        $isRejected = $warehouseOrder->status->value === 'Rejected';

        // Get items and adjust stock for edit mode
        // In edit mode (except rejected): max quantity = current stock + already ordered quantity
        // In rejected mode: max quantity = current stock (stock was already returned)
        $items = Item::whereNull('division_id')
            ->where(function ($query) use ($orderedQuantities) {
                $query->where('stock', '>', 0)
                    ->orWhereIn('id', array_keys($orderedQuantities));
            })
            ->get(['id', 'name', 'stock', 'unit_of_measure', 'category_id', 'description'])
            ->map(function ($item) use ($orderedQuantities, $isRejected) {
                // Only add back the ordered quantity if NOT rejected
                // Rejected orders already have their stock returned
                if (!$isRejected && isset($orderedQuantities[$item->id])) {
                    $item->stock += $orderedQuantities[$item->id];
                }
                return $item;
            });

        return Inertia::render('Inventory/WarehouseOrder/Create', [
            'warehouseOrder' => $warehouseOrder,
            'items' => $items,
            'categories' => \Modules\Inventory\Models\CategoryItem::all(['id', 'name']),
            'divisions' => Division::where('is_active', true)->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, WarehouseOrder $warehouseOrder)
    {
        if (! $this->warehouseOrderService->canEdit($warehouseOrder)) {
            abort(403, 'Permintaan barang yang sudah diproses tidak dapat diubah.');
        }

        $validated = $request->validate([
            'description' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $this->warehouseOrderService->update($warehouseOrder, $validated);

        return to_route('inventory.warehouse-orders.index')
            ->with('success', 'Permintaan barang berhasil diperbarui.');
    }

    public function delete(WarehouseOrder $warehouseOrder)
    {
        if (! $this->warehouseOrderService->canEdit($warehouseOrder)) {
            abort(403, 'Permintaan barang yang sudah diproses tidak dapat dihapus.');
        }

        $this->warehouseOrderService->delete($warehouseOrder);

        return to_route('inventory.warehouse-orders.index')
            ->with('success', 'Permintaan barang berhasil dihapus.');
    }

    public function datatable(DatatableRequest $request)
    {
        return $this->datatableService->getDatatable($request, $request->user());
    }

    public function printExcel(DatatableRequest $request)
    {
        return $this->datatableService->printExcel($request, $request->user());
    }

    public function confirm(WarehouseOrder $warehouseOrder)
    {
        if (! $this->warehouseOrderService->canConfirm($warehouseOrder)) {
            abort(403, 'Hanya permintaan dengan status Pending atau Revision yang bisa dikonfirmasi.');
        }

        $this->warehouseOrderService->confirm($warehouseOrder);

        return back()->with('success', 'Permintaan barang berhasil dikonfirmasi.');
    }

    public function reject(Request $request, WarehouseOrder $warehouseOrder)
    {
        if (! $this->warehouseOrderService->canConfirm($warehouseOrder)) {
            abort(403, 'Hanya permintaan dengan status Pending atau Revision yang bisa ditolak.');
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $this->warehouseOrderService->reject($warehouseOrder, $validated['reason'], $request->user());

        return back()->with('success', 'Permintaan barang berhasil ditolak.');
    }

    public function delivery(WarehouseOrder $warehouseOrder)
    {
        if (! $this->warehouseOrderService->canDeliver($warehouseOrder)) {
            abort(403, 'Hanya permintaan dengan status Confirmed yang bisa diserahkan.');
        }

        return Inertia::render('Inventory/WarehouseOrder/Delivery', [
            'order' => $warehouseOrder->load(['user', 'division', 'carts.item']),
        ]);
    }

    public function deliver(Request $request, WarehouseOrder $warehouseOrder)
    {
        if (! $this->warehouseOrderService->canDeliver($warehouseOrder)) {
            abort(403, 'Hanya permintaan dengan status Confirmed yang bisa diserahkan.');
        }

        $validated = $request->validate([
            'delivery_date' => 'required|date',
            'delivery_images' => 'required|array|min:1',
            'delivery_images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $this->warehouseOrderService->deliver($warehouseOrder, $validated, $request->user());

        return to_route('inventory.warehouse-orders.index')
            ->with('success', 'Barang berhasil diserahkan.');
    }

    public function received(WarehouseOrder $warehouseOrder)
    {
        $user = auth()->user();

        if (! $this->warehouseOrderService->canReceive($warehouseOrder, $user)) {
            abort(403, 'Hanya permintaan dengan status Delivered yang bisa diterima.');
        }

        return Inertia::render('Inventory/WarehouseOrder/Receive', [
            'order' => $warehouseOrder->load(['user', 'division', 'carts.item.category']),
        ]);
    }

    public function receive(Request $request, WarehouseOrder $warehouseOrder)
    {
        $user = $request->user();

        if (! $this->warehouseOrderService->canReceive($warehouseOrder, $user)) {
            abort(403, 'Anda tidak memiliki akses untuk menerima permintaan ini.');
        }

        // Debug log
        \Log::info('Receive request data:', [
            'all' => $request->all(),
            'files' => $request->allFiles(),
            'receipt_date' => $request->input('receipt_date'),
            'receipt_images' => $request->file('receipt_images'),
        ]);

        $validated = $request->validate([
            'receipt_date' => 'required|date',
            'receipt_images' => 'required|array|min:1',
            'receipt_images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        \Log::info('Validation passed, calling service', ['validated' => $validated]);

        $this->warehouseOrderService->receive(
            $warehouseOrder,
            $validated['receipt_date'],
            $validated['receipt_images'],
            $user
        );

        \Log::info('Service completed successfully');

        return to_route('inventory.warehouse-orders.index')
            ->with('success', 'Barang berhasil diterima.');
    }
}
