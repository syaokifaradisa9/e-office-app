<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\DatatableRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Inventory\Datatables\StockOpnameDatatableService;
use Modules\Inventory\Enums\InventoryPermission;
use Modules\Inventory\Http\Requests\StoreStockOpnameRequest;
use Modules\Inventory\Http\Requests\UpdateStockOpnameRequest;
use Modules\Inventory\Models\StockOpname;
use Modules\Inventory\Services\LookupService;
use Modules\Inventory\Services\StockOpnameService;

class StockOpnameController extends Controller
{
    public function __construct(
        private StockOpnameService $stockOpnameService,
        private StockOpnameDatatableService $datatableService,
        private LookupService $lookupService
    ) {}

    public function index(Request $request, string $type = 'all')
    {
        /** @var User $user */
        $user = auth()->user();
        $divisionId = $type === 'division' ? $user->division_id : null;

        return Inertia::render('Inventory/StockOpname/Index', [
            'type' => $type,
            'divisions' => $this->lookupService->getActiveDivisions(),
            'isMenuHidden' => $this->stockOpnameService->isMenuHidden($divisionId),
        ]);
    }

    public function create(Request $request, string $type = 'warehouse')
    {
        if (! in_array($type, ['warehouse', 'division'])) {
            abort(404);
        }

        /** @var User $user */
        $user = $request->user();

        if ($type === 'warehouse' && ! $user->can(InventoryPermission::CreateStockOpname->value)) {
            abort(403);
        }
        if ($type === 'division' && ! $user->can(InventoryPermission::CreateStockOpname->value)) {
            abort(403);
        }

        return Inertia::render('Inventory/StockOpname/Create', [
            'type' => $type,
            'divisions' => $this->lookupService->getActiveDivisions(),
        ]);
    }

    public function store(StoreStockOpnameRequest $request, string $type = 'warehouse')
    {
        $user = $request->user();
        $dto = StockOpnameDTO::fromStoreRequest($request);

        try {
            $this->stockOpnameService->initializeOpname($dto, $user);
            $message = $type === 'warehouse' ? 'Stok Opname Gudang berhasil diinisialisasi.' : 'Stok Opname Divisi berhasil diinisialisasi.';

            return to_route('inventory.stock-opname.index', ['type' => $type])->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function process(string $type, StockOpname $stockOpname)
    {
        $user = auth()->user();

        if (! $this->stockOpnameService->canProcess($stockOpname, $user)) {
            abort(403, 'Anda tidak memiliki akses untuk memproses opname ini.');
        }

        $items = $this->stockOpnameService->getItemsForOpname($user, $stockOpname->division_id);

        return Inertia::render('Inventory/StockOpname/Process', [
            'opname' => $stockOpname->load(['items.item']),
            'items' => $items,
            'type' => $type,
        ]);
    }

    public function storeProcess(ProcessStockOpnameRequest $request, string $type, StockOpname $stockOpname)
    {
        $user = $request->user();
        $dto = StockOpnameDTO::fromProcessRequest($request);

        try {
            $this->stockOpnameService->savePhysicalStock($stockOpname, $dto, $user);
            $message = $dto->status === 'Confirmed' ? 'Stok Opname berhasil dikonfirmasi.' : 'Proses Stok Opname berhasil disimpan.';

            return to_route('inventory.stock-opname.index', ['type' => $type])->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function finalize(string $type, StockOpname $stockOpname)
    {
        $user = auth()->user();

        if (! $this->stockOpnameService->canFinalize($stockOpname, $user)) {
            abort(403, 'Anda tidak memiliki akses untuk finalisasi opname ini.');
        }

        return Inertia::render('Inventory/StockOpname/Finalize', [
            'opname' => $stockOpname->load(['items.item', 'division']),
            'type' => $type,
        ]);
    }

    public function storeFinalize(FinalizeStockOpnameRequest $request, string $type, StockOpname $stockOpname)
    {
        $user = $request->user();
        $dto = StockOpnameDTO::fromFinalizeRequest($request);

        try {
            $this->stockOpnameService->finalizeStock($stockOpname, $dto, $user);
            return to_route('inventory.stock-opname.index', ['type' => $type])->with('success', 'Stok Opname berhasil difinalisasi.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
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
        /** @var User $user */
        $user = $request->user();

        if (! $this->stockOpnameService->canManage($stockOpname, $user)) {
            abort(403, 'Unauthorized');
        }

        return Inertia::render('Inventory/StockOpname/Create', [
            'opname' => $stockOpname,
            'type' => $type,
            'divisions' => $this->lookupService->getActiveDivisions(),
        ]);
    }

    public function update(StoreStockOpnameRequest $request, string $type, StockOpname $stockOpname)
    {
        // Using StoreStockOpnameRequest because rules are same for updating Pending opname
        $dto = StockOpnameDTO::fromStoreRequest($request);

        try {
            $stockOpname->update($dto->toArray());
            return to_route('inventory.stock-opname.index', ['type' => $type])->with('success', 'Stok Opname berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function delete(Request $request, string $type, StockOpname $stockOpname)
    {
        if (! $this->stockOpnameService->canManage($stockOpname, $request->user())) {
            abort(403);
        }

        try {
            $stockOpname->items()->delete();
            $stockOpname->delete();
            return to_route('inventory.stock-opname.index', ['type' => $type])->with('success', 'Stok Opname berhasil dihapus.');
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
}
