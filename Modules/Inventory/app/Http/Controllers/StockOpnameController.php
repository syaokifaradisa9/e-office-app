<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\DatatableRequest;
use App\Models\Division;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Inventory\Datatables\StockOpnameDatatableService;
use Modules\Inventory\Enums\InventoryPermission;
use Modules\Inventory\Models\StockOpname;
use Modules\Inventory\Services\StockOpnameService;

class StockOpnameController extends Controller
{
    public function __construct(
        private StockOpnameService $stockOpnameService,
        private StockOpnameDatatableService $datatableService
    ) {}

    public function index(Request $request, string $type = 'all')
    {
        $divisions = Division::where('is_active', true)->get(['id', 'name']);

        return Inertia::render('Inventory/StockOpname/Index', [
            'type' => $type,
            'divisions' => $divisions,
        ]);
    }

    public function create(Request $request, string $type = 'warehouse')
    {
        if (! in_array($type, ['warehouse', 'division'])) {
            abort(404);
        }

        $user = $request->user();

        // Check permission based on type
        if ($type === 'warehouse' && ! $user->can(InventoryPermission::ManageWarehouseStockOpname->value)) {
            abort(403);
        }
        if ($type === 'division' && ! $user->can(InventoryPermission::ManageDivisionStockOpname->value)) {
            abort(403);
        }

        $items = $this->stockOpnameService->getItemsForOpname($user, $type);

        return Inertia::render('Inventory/StockOpname/Create', [
            'items' => $items,
            'type' => $type,
            'divisions' => Division::where('is_active', true)->get(['id', 'name']),
        ]);
    }

    public function store(Request $request, string $type = 'warehouse')
    {
        if (! in_array($type, ['warehouse', 'division'])) {
            abort(404);
        }

        $user = $request->user();

        // Check permission based on type
        if ($type === 'warehouse' && ! $user->can(InventoryPermission::ManageWarehouseStockOpname->value)) {
            abort(403);
        }
        if ($type === 'division' && ! $user->can(InventoryPermission::ManageDivisionStockOpname->value)) {
            abort(403);
        }

        $validated = $request->validate([
            'opname_date' => 'required|date',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.physical_stock' => 'required|integer|min:0',
            'items.*.notes' => 'nullable|string',
        ]);

        if ($type === 'warehouse') {
            $this->stockOpnameService->createWarehouse($validated, $user);
            $message = 'Stok Opname Gudang berhasil disimpan.';
        } else {
            $this->stockOpnameService->createDivision($validated, $user);
            $message = 'Stok Opname Divisi berhasil disimpan.';
        }

        return to_route('inventory.stock-opname.index', ['type' => $type])->with('success', $message);
    }

    public function show(string $type, StockOpname $stockOpname)
    {
        $user = auth()->user();

        if (! $this->stockOpnameService->canView($stockOpname, $user)) {
            abort(403);
        }

        return Inertia::render('Inventory/StockOpname/Show', [
            'opname' => $stockOpname->load(['user', 'division', 'items.item']),
        ]);
    }

    public function edit(Request $request, string $type, StockOpname $stockOpname)
    {
        if (! in_array($type, ['warehouse', 'division'])) {
            abort(404);
        }

        $user = $request->user();

        if (! $this->stockOpnameService->canManage($stockOpname, $user)) {
            abort(403, 'Unauthorized');
        }

        $items = $this->stockOpnameService->getItemsForOpname($user, $type);

        return Inertia::render('Inventory/StockOpname/Create', [
            'opname' => $stockOpname->load('items.item'),
            'items' => $items,
            'type' => $type,
            'divisions' => Division::where('is_active', true)->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, string $type, StockOpname $stockOpname)
    {
        if (! in_array($type, ['warehouse', 'division'])) {
            abort(404);
        }

        $validated = $request->validate([
            'opname_date' => 'required|date',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.physical_stock' => 'required|integer|min:0',
            'items.*.notes' => 'nullable|string',
        ]);

        try {
            $this->stockOpnameService->update($stockOpname, $validated, $request->user());
            $message = $type === 'warehouse' ? 'Stok Opname Gudang berhasil diperbarui.' : 'Stok Opname Divisi berhasil diperbarui.';

            return to_route('inventory.stock-opname.index', ['type' => $type])->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function delete(Request $request, string $type, StockOpname $stockOpname)
    {
        if (! in_array($type, ['warehouse', 'division'])) {
            abort(404);
        }

        try {
            $this->stockOpnameService->delete($stockOpname, $request->user());
            $message = $type === 'warehouse' ? 'Stok Opname Gudang berhasil dihapus.' : 'Stok Opname Divisi berhasil dihapus.';

            return to_route('inventory.stock-opname.index', ['type' => $type])->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function datatable(DatatableRequest $request, string $type = 'all')
    {
        return $this->datatableService->getDatatable($request, $request->user(), $type);
    }

    public function printExcel(DatatableRequest $request, string $type = 'all')
    {
        return $this->datatableService->printExcel($request, $request->user(), $type);
    }

    public function confirm(Request $request, StockOpname $stockOpname)
    {
        $user = $request->user();

        if (! $user->can(InventoryPermission::ConfirmStockOpname->value)) {
            abort(403);
        }

        try {
            $this->stockOpnameService->confirm($stockOpname, $user);
            $type = $this->stockOpnameService->getType($stockOpname);

            return to_route('inventory.stock-opname.index', ['type' => $type])
                ->with('success', 'Stock opname berhasil dikonfirmasi.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
