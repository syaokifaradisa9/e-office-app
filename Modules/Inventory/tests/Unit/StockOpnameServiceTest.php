<?php

namespace Modules\Inventory\Tests\Unit;

use App\Models\Division;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Inventory\DataTransferObjects\StockOpnameDTO;
use Modules\Inventory\Enums\InventoryPermission;
use Modules\Inventory\Enums\StockOpnameStatus;
use Modules\Inventory\Models\CategoryItem;
use Modules\Inventory\Models\Item;
use Modules\Inventory\Models\ItemTransaction;
use Modules\Inventory\Models\StockOpname;
use Modules\Inventory\Services\StockOpnameService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(StockOpnameService::class);
    
    // Create permissions
    foreach (InventoryPermission::values() as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }
    
    // Create division
    $this->division = Division::factory()->create(['is_active' => true]);
    
    // Create category and items
    $this->category = CategoryItem::factory()->create();
    $this->warehouseItem = Item::factory()->create([
        'category_id' => $this->category->id,
        'division_id' => null,
        'stock' => 100,
    ]);
    $this->divisionItem = Item::factory()->create([
        'category_id' => $this->category->id,
        'division_id' => $this->division->id,
        'stock' => 50,
    ]);
});

// ============================================
// getItemsForOpname
// ============================================

describe('getItemsForOpname', function () {
    
    /**
     * Memastikan service mengembalikan daftar barang dari gudang utama jika division_id bernilai null.
     */
    it('returns warehouse items if division_id is null', function () {
        $user = User::factory()->create();
        
        $items = $this->service->getItemsForOpname($user, null);
        
        expect($items->count())->toBeGreaterThan(0);
        expect($items->first()->division_id)->toBeNull();
    });
    
    /**
     * Memastikan service mengembalikan daftar barang dari divisi tertentu jika ID divisi diberikan.
     */
    it('returns division items if division_id is provided', function () {
        $user = User::factory()->create(['division_id' => $this->division->id]);
        
        $items = $this->service->getItemsForOpname($user, $this->division->id);
        
        expect($items->count())->toBeGreaterThan(0);
    });
});

// ============================================
// initializeOpname
// ============================================

describe('initializeOpname', function () {
    
    /**
     * Memastikan inisialisasi stock opname gudang berhasil dan status awal adalah 'Pending'.
     */
    it('can initialize warehouse stock opname with Pending status', function () {
        $user = User::factory()->create();
        
        $dto = new StockOpnameDTO(
            opname_date: now()->format('Y-m-d'),
            division_id: null,
            notes: 'Test opname gudang',
            status: StockOpnameStatus::Pending
        );
        
        $opname = $this->service->initializeOpname($dto, $user);
        
        expect($opname)->toBeInstanceOf(StockOpname::class);
        expect($opname->user_id)->toBe($user->id);
        expect($opname->division_id)->toBeNull();
        expect($opname->status)->toBe(StockOpnameStatus::Pending);
    });
    
    /**
     * Memastikan sistem menolak inisialisasi jika masih terdapat proses opname yang belum selesai di gudang.
     */
    it('fails to initialize if an active opname already exists in the warehouse', function () {
        $user = User::factory()->create();
        
        StockOpname::create([
            'user_id' => $user->id,
            'division_id' => null,
            'opname_date' => now(),
            'status' => StockOpnameStatus::Pending,
        ]);
        
        $dto = new StockOpnameDTO(
            opname_date: now()->format('Y-m-d'),
            division_id: null,
            status: StockOpnameStatus::Pending
        );
        
        expect(fn () => $this->service->initializeOpname($dto, $user))
            ->toThrow(\Exception::class, 'belum selesai');
    });
});

// ============================================
// savePhysicalStock (Draft)
// ============================================

describe('savePhysicalStock draft', function () {
    
    /**
     * Memastikan data stok fisik dapat disimpan sebagai draf dengan status proses.
     */
    it('successfully saves physical stock as a draft with Process status', function () {
        $user = User::factory()->create();
        
        $opname = StockOpname::create([
            'user_id' => $user->id,
            'division_id' => null,
            'opname_date' => now(),
            'status' => StockOpnameStatus::Pending,
        ]);
        
        $dto = new StockOpnameDTO(
            items: [
                ['item_id' => $this->warehouseItem->id, 'physical_stock' => 95, 'notes' => 'Selisih 5'],
            ],
            status: StockOpnameStatus::Proses
        );
        
        $result = $this->service->savePhysicalStock($opname, $dto, $user);
        
        expect($result->status)->toBe(StockOpnameStatus::Proses);
        expect($result->items->count())->toBe(1);
        expect($result->items->first()->physical_stock)->toBe(95);
    });
});

// ============================================
// savePhysicalStock (Confirm → Stock Opname)
// ============================================

describe('savePhysicalStock confirm', function () {
    
    /**
     * Memastikan aksi konfirmasi berhasil mengubah status proses menjadi 'Stock Opname'.
     */
    it('changes status to Stock Opname upon confirmation', function () {
        $user = User::factory()->create();
        
        $opname = StockOpname::create([
            'user_id' => $user->id,
            'division_id' => null,
            'opname_date' => now(),
            'status' => StockOpnameStatus::Pending,
        ]);
        
        $dto = new StockOpnameDTO(
            items: [
                ['item_id' => $this->warehouseItem->id, 'physical_stock' => 85],
            ],
            status: StockOpnameStatus::StockOpname
        );
        
        $result = $this->service->savePhysicalStock($opname, $dto, $user);
        
        expect($result->status)->toBe(StockOpnameStatus::StockOpname);
    });

    /**
     * Memastikan field physical_stock otomatis diisi 0 jika dibiarkan kosong saat konfirmasi.
     */
    it('sets physical_stock to 0 if left empty during confirmation', function () {
        $user = User::factory()->create();
        
        $opname = StockOpname::create([
            'user_id' => $user->id,
            'division_id' => null,
            'opname_date' => now(),
            'status' => StockOpnameStatus::Pending,
        ]);
        
        $dto = new StockOpnameDTO(
            items: [
                ['item_id' => $this->warehouseItem->id, 'physical_stock' => null],
            ],
            status: StockOpnameStatus::StockOpname
        );
        
        // Items created with initial stock 100 in beforeEach
        $result = $this->service->savePhysicalStock($opname, $dto, $user);
        
        expect($result->items->first()->physical_stock)->toBe(0);
        expect($result->items->first()->difference)->toBe(-100);
    });
});

// ============================================
// isMenuHidden
// ============================================

describe('isMenuHidden', function () {
    
    /**
     * Memastikan menu opname disembunyikan jika sedang ada proses opname aktif di gudang utama.
     */
    it('returns true if an active opname exists in the main warehouse', function () {
        $user = User::factory()->create();
        
        StockOpname::create([
            'user_id' => $user->id,
            'division_id' => null,
            'opname_date' => now(),
            'status' => StockOpnameStatus::Pending,
        ]);
        
        expect($this->service->isMenuHidden($this->division->id))->toBeTrue();
    });

    /**
     * Memastikan menu tidak disembunyikan jika status opname sudah mencapai tahap akhir 'Stock Opname'.
     */
    it('returns false if opname status is already Stock Opname', function () {
        $user = User::factory()->create();
        
        StockOpname::create([
            'user_id' => $user->id,
            'division_id' => null,
            'opname_date' => now(),
            'status' => StockOpnameStatus::StockOpname,
        ]);
        
        expect($this->service->isMenuHidden($this->division->id))->toBeFalse();
    });
});

// ============================================
// canManage & canView & canProcess & canFinalize
// ============================================

describe('canManage dan canView', function () {
    
    /**
     * Memastikan user dengan izin yang sesuai dapat memproses opname yang berstatus 'Pending'.
     */
    it('allows processing for Pending opname status', function () {
        $role = Role::firstOrCreate(['name' => 'Processor', 'guard_name' => 'web']);
        $role->syncPermissions([InventoryPermission::ProcessStockOpname->value]);
        
        $user = User::factory()->create();
        $user->assignRole($role);
        
        $opname = StockOpname::create([
            'user_id' => $user->id,
            'division_id' => null,
            'opname_date' => now(),
            'status' => StockOpnameStatus::Pending,
        ]);
        
        expect($this->service->canProcess($opname, $user))->toBeTrue();
    });
    
    /**
     * Memastikan user dengan izin yang sesuai dapat memproses opname yang berstatus 'Process'.
     */
    it('allows processing for Process opname status', function () {
        $role = Role::firstOrCreate(['name' => 'Processor', 'guard_name' => 'web']);
        $role->syncPermissions([InventoryPermission::ProcessStockOpname->value]);
        
        $user = User::factory()->create();
        $user->assignRole($role);
        
        $opname = StockOpname::create([
            'user_id' => $user->id,
            'division_id' => null,
            'opname_date' => now(),
            'status' => StockOpnameStatus::Proses,
        ]);
        
        expect($this->service->canProcess($opname, $user))->toBeTrue();
    });
    
    /**
     * Memastikan user dengan izin finalisasi dapat menyelesaikan opname yang berstatus 'Stock Opname'.
     */
    it('allows finalization for Stock Opname status', function () {
        $role = Role::firstOrCreate(['name' => 'Finalizer', 'guard_name' => 'web']);
        $role->syncPermissions([InventoryPermission::FinalizeStockOpname->value]);
        
        $user = User::factory()->create();
        $user->assignRole($role);
        
        $opname = StockOpname::create([
            'user_id' => $user->id,
            'division_id' => null,
            'opname_date' => now()->subDay(), // Must be at least 1 day before today
            'status' => StockOpnameStatus::StockOpname,
            'confirmed_at' => now()->subDay(),
        ]);
        
        expect($this->service->canFinalize($opname, $user))->toBeTrue();
    });
});
