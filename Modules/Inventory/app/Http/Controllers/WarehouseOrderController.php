<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\DatatableRequest;
use Inertia\Inertia;
use Modules\Inventory\DataTransferObjects\WarehouseOrderDTO;
use Modules\Inventory\Datatables\WarehouseOrderDatatableService;
use Modules\Inventory\Http\Requests\DeliverWarehouseOrderRequest;
use Modules\Inventory\Http\Requests\ReceiveWarehouseOrderRequest;
use Modules\Inventory\Http\Requests\RejectWarehouseOrderRequest;
use Modules\Inventory\Http\Requests\StoreWarehouseOrderRequest;
use Modules\Inventory\Http\Requests\UpdateWarehouseOrderRequest;
use Modules\Inventory\Models\WarehouseOrder;
use Modules\Inventory\Services\LookupService;
use Modules\Inventory\Services\WarehouseOrderService;

class WarehouseOrderController extends Controller
{
    public function __construct(
        private WarehouseOrderService $warehouseOrderService,
        private WarehouseOrderDatatableService $datatableService,
        private LookupService $lookupService
    ) {}

    public function index()
    {
        return Inertia::render('Inventory/WarehouseOrder/Index', [
            'users' => $this->lookupService->getActiveUsers(),
            'divisions' => $this->lookupService->getActiveDivisions(),
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
            'items' => $this->lookupService->getWarehouseItemsWithStock(),
            'categories' => $this->lookupService->getActiveCategories(),
            'userDivision' => [
                'id' => $user->division_id,
                'name' => $this->lookupService->getDivisionName($user->division_id)
            ],
        ]);
    }

    public function store(StoreWarehouseOrderRequest $request)
    {
        $dto = WarehouseOrderDTO::fromRequest($request);
        $this->warehouseOrderService->store($dto, $request->user());

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
            'order' => $warehouseOrder->load(['user', 'division', 'carts.item.category', 'deliveredBy', 'receivedBy', 'latestReject']),
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

        return Inertia::render('Inventory/WarehouseOrder/Create', [
            'warehouseOrder' => $warehouseOrder,
            'items' => $this->lookupService->getItemsForOrder($orderedQuantities, $isRejected),
            'categories' => $this->lookupService->getActiveCategories(),
            'divisions' => $this->lookupService->getActiveDivisions(),
        ]);
    }

    public function update(UpdateWarehouseOrderRequest $request, WarehouseOrder $warehouseOrder)
    {
        if (! $this->warehouseOrderService->canEdit($warehouseOrder)) {
            abort(403, 'Permintaan barang yang sudah diproses tidak dapat diubah.');
        }

        $validated = $request->validated();

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

    public function reject(RejectWarehouseOrderRequest $request, WarehouseOrder $warehouseOrder)
    {
        if (! $this->warehouseOrderService->canConfirm($warehouseOrder)) {
            abort(403, 'Hanya permintaan dengan status Pending atau Revision yang bisa ditolak.');
        }

        $validated = $request->validated();

        $this->warehouseOrderService->reject($warehouseOrder, $validated['reason'], $request->user());

        return back()->with('success', 'Permintaan barang berhasil ditolak.');
    }

    public function delivery(WarehouseOrder $warehouseOrder)
    {
        if (! $this->warehouseOrderService->canDeliver($warehouseOrder)) {
            abort(403, 'Hanya permintaan dengan status Confirmed yang bisa diserahkan.');
        }

        return Inertia::render('Inventory/WarehouseOrder/Delivery', [
            'order' => $warehouseOrder->load(['user', 'division', 'carts.item.category']),
        ]);
    }

    public function deliver(DeliverWarehouseOrderRequest $request, WarehouseOrder $warehouseOrder)
    {
        if (! $this->warehouseOrderService->canDeliver($warehouseOrder)) {
            abort(403, 'Hanya permintaan dengan status Confirmed yang bisa diserahkan.');
        }

        $validated = $request->validated();

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

    public function receive(ReceiveWarehouseOrderRequest $request, WarehouseOrder $warehouseOrder)
    {
        $user = $request->user();

        if (! $this->warehouseOrderService->canReceive($warehouseOrder, $user)) {
            abort(403, 'Anda tidak memiliki akses untuk menerima permintaan ini.');
        }

        $validated = $request->validated();

        $this->warehouseOrderService->receive(
            $warehouseOrder,
            $validated['receipt_date'],
            $validated['receipt_images'],
            $user
        );

        return to_route('inventory.warehouse-orders.index')
            ->with('success', 'Barang berhasil diterima.');
    }
}
