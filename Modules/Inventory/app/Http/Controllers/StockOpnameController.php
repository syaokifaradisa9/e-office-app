<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\DatatableRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Inventory\Datatables\StockOpnameDatatableService;
use Modules\Inventory\Enums\InventoryPermission;
use Modules\Inventory\DataTransferObjects\StockOpnameDTO;
use Modules\Inventory\Http\Requests\FinalizeStockOpnameRequest;
use Modules\Inventory\Http\Requests\ProcessStockOpnameRequest;
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

    /**
     * Index page for stock opname
     * - /stock-opname/all → requires ViewAllStockOpname permission (shows all data)
     * - /stock-opname/division → requires ViewDivisionStockOpname permission
     * - /stock-opname/warehouse → requires ViewWarehouseStockOpname permission
     */
    public function index(Request $request, string $type = 'all')
    {
        /** @var User $user */
        $user = auth()->user();

        // Permission check per type
        if ($type === 'division' && ! $user->can(InventoryPermission::ViewDivisionStockOpname->value)) {
            abort(403);
        }
        if ($type === 'warehouse' && ! $user->can(InventoryPermission::ViewWarehouseStockOpname->value)) {
            abort(403);
        }
        if ($type === 'all' && ! $user->can(InventoryPermission::ViewAllStockOpname->value)) {
            abort(403);
        }

        return Inertia::render('Inventory/StockOpname/Index', [
            'type' => $type,
            'divisions' => $this->lookupService->getActiveDivisions(),
            'isMenuHidden' => $this->stockOpnameService->isMenuHidden($user->division_id),
        ]);
    }

    /**
     * Create form for stock opname
     * - /stock-opname/warehouse/create → requires CreateStockOpname
     * - /stock-opname/division/create → requires CreateStockOpname
     */
    public function create(Request $request, string $type = 'warehouse')
    {
        if (! in_array($type, ['warehouse', 'division'])) {
            abort(404);
        }

        /** @var User $user */
        $user = $request->user();

        if (! $user->can(InventoryPermission::CreateStockOpname->value)) {
            abort(403);
        }

        return Inertia::render('Inventory/StockOpname/Create', [
            'type' => $type,
            'divisions' => $this->lookupService->getActiveDivisions(),
        ]);
    }

    /**
     * Store stock opname (status = Pending)
     * warehouse type → division_id must be null
     * division type → division_id = user's division_id
     */
    public function store(StoreStockOpnameRequest $request, string $type = 'warehouse')
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->can(InventoryPermission::CreateStockOpname->value)) {
            abort(403);
        }

        $dto = StockOpnameDTO::fromStoreRequest($request);

        // Enforce division_id based on type
        if ($type === 'warehouse') {
            $dto = new StockOpnameDTO(
                opname_date: $dto->opname_date,
                division_id: null,
                notes: $dto->notes,
                status: \Modules\Inventory\Enums\StockOpnameStatus::Pending
            );
        } elseif ($type === 'division') {
            $dto = new StockOpnameDTO(
                opname_date: $dto->opname_date,
                division_id: $user->division_id,
                notes: $dto->notes,
                status: \Modules\Inventory\Enums\StockOpnameStatus::Pending
            );
        }

        try {
            $this->stockOpnameService->initializeOpname($dto, $user);
            $message = $type === 'warehouse'
                ? 'Stok Opname Gudang berhasil diinisialisasi.'
                : 'Stok Opname Divisi berhasil diinisialisasi.';

            return to_route('inventory.stock-opname.index', ['type' => $type])->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Process page for stock opname
     * Requires ProcessStockOpname permission
     */
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

    /**
     * Store process result
     * Draft → status "Dalam Proses"
     * Confirm → status "Stock Opname"
     */
    public function storeProcess(ProcessStockOpnameRequest $request, string $type, StockOpname $stockOpname)
    {
        $user = $request->user();

        if (! $this->stockOpnameService->canProcess($stockOpname, $user)) {
            abort(403, 'Anda tidak memiliki akses untuk memproses opname ini.');
        }

        $dto = StockOpnameDTO::fromProcessRequest($request);

        try {
            $this->stockOpnameService->savePhysicalStock($stockOpname, $dto, $user);

            $message = $dto->status === 'Stock Opname'
                ? 'Stok Opname berhasil dikonfirmasi. Stok barang telah disesuaikan.'
                : 'Proses Stok Opname berhasil disimpan sebagai draft.';

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

        try {
            $method = new \ReflectionMethod($this->stockOpnameService, 'validateFinalizationRule');
            $method->setAccessible(true);
            $method->invoke($this->stockOpnameService, $stockOpname);
        } catch (\Exception $e) {
            return to_route('inventory.stock-opname.index', ['type' => $type])->with('error', $e->getMessage());
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
