<?php

use App\Models\Division;
use App\Models\User;
use Modules\Inventory\Enums\InventoryPermission;
use Modules\Inventory\Enums\StockOpnameStatus;
use Modules\Inventory\Models\CategoryItem;
use Modules\Inventory\Models\Item;
use Modules\Inventory\Models\ItemTransaction;
use Modules\Inventory\Models\StockOpname;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    // Create all permissions
    $permissions = InventoryPermission::values();
    
    foreach ($permissions as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }
    
    // Create full permission role
    $this->fullRole = Role::firstOrCreate(['name' => 'Full Stock Opname', 'guard_name' => 'web']);
    $this->fullRole->syncPermissions($permissions);
    
    // Create user
    $this->division = Division::factory()->create(['is_active' => true]);
    $this->testUser = User::factory()->create(['division_id' => $this->division->id]);
    $this->testUser->assignRole($this->fullRole);
    
    // Create items
    $this->category = CategoryItem::factory()->create();
    $this->warehouseItem = Item::factory()->create([
        'category_id' => $this->category->id,
        'division_id' => null,
        'stock' => 100,
    ]);
});

// ============================================
// PERMISSION INDEX
// ============================================

describe('Index Permission', function () {
    /**
     * Memastikan user dengan izin 'lihat_stock_opname_warehouse' dapat mengakses halaman indeks opname gudang.
     */
    it('allows warehouse index access with ViewWarehouseStockOpname permission', function () {
        // 1. Berikan izin spesifik
        $this->fullRole->syncPermissions([InventoryPermission::ViewWarehouseStockOpname->value]);
        
        // 2. Validasi akses OK (200)
        $response = $this->actingAs($this->testUser)->get('/inventory/stock-opname/warehouse');
        $response->assertOk();
    });
});

// ============================================
// CRUD OPERATIONS
// ============================================

describe('CRUD Operations', function () {
    /**
     * Test proses inisiasi (store) stock opname gudang.
     */
    it('stores warehouse opname with null division_id and Pending status', function () {
        // 1. Aksi inisiasi opname gudang (tanpa divisi)
        $response = $this->actingAs($this->testUser)->post('/inventory/stock-opname/warehouse/store', [
            'opname_date' => now()->format('Y-m-d'),
            'notes' => 'Test opname gudang',
        ]);
        
        // 2. Validasi redirect dan data tersimpan dengan status 'Pending'
        $response->assertRedirect();
        $this->assertDatabaseHas('stock_opnames', [
            'user_id' => $this->testUser->id,
            'division_id' => null,
            'status' => StockOpnameStatus::Pending->value,
        ]);
    });
});

// ============================================
// PROCESS (Simpan Draf → Process)
// ============================================

describe('Process - Save Draft', function () {
    /**
     * Memastikan "Simpan Draf" pada opname gudang merubah status menjadi 'Proses'.
     */
    it('changes status to Process when saving as draft', function () {
        // 1. Persiapan data opname status 'Pending'
        $opname = StockOpname::factory()->create([
            'user_id' => $this->testUser->id,
            'division_id' => null,
            'status' => StockOpnameStatus::Pending,
        ]);
        
        // 2. Aksi simpan draf (input jumlah fisik = 95)
        $response = $this->actingAs($this->testUser)->post("/inventory/stock-opname/warehouse/{$opname->id}/process", [
            'items' => [
                ['item_id' => $this->warehouseItem->id, 'physical_stock' => 95],
            ],
        ]);
        
        // 3. Validasi status berubah jadi 'Proses'
        $response->assertRedirect();
        $opname->refresh();
        expect($opname->status)->toBe(StockOpnameStatus::Proses);
    });
});

// ============================================
// PROCESS (Konfirmasi → Stock Opname)
// ============================================

describe('Process - Confirm', function () {
    /**
     * Memastikan "Konfirmasi" pada opname gudang merubah status menjadi 'Stock Opname' (Final).
     */
    it('changes status to Stock Opname when confirmed', function () {
        // 1. Persiapan opname Pending
        $opname = StockOpname::factory()->create([
            'user_id' => $this->testUser->id,
            'division_id' => null,
            'status' => StockOpnameStatus::Pending,
        ]);
        
        // 2. Aksi konfirmasi dengan flag 'confirm' => true
        $response = $this->actingAs($this->testUser)->post("/inventory/stock-opname/warehouse/{$opname->id}/process", [
            'items' => [
                ['item_id' => $this->warehouseItem->id, 'physical_stock' => 85],
            ],
            'confirm' => true,
        ]);
        
        // 3. Validasi status akhir
        $response->assertRedirect();
        $opname->refresh();
        expect($opname->status)->toBe(StockOpnameStatus::StockOpname);
    });
});

// ============================================
// DATATABLE
// ============================================

describe('Datatable', function () {
    /**
     * Test fitur search pada datatable list opname.
     */
    it('handles datatable search correctly', function () {
        // 1. Persiapan data dengan catatan khusus
        StockOpname::factory()->create([
            'user_id' => $this->testUser->id,
            'division_id' => null,
            'notes' => 'Opname tahunan gudang',
        ]);
        
        // 2. Request datatable dengan kata kunci "tahunan"
        $response = $this->actingAs($this->testUser)->get('/inventory/stock-opname/datatable/all?search=tahunan');
        
        // 3. Validasi data ditemukan
        $response->assertOk();
        $data = $response->json();
        expect(count($data['data']))->toBe(1);
    });
});
