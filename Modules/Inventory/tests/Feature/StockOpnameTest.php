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

describe('Permission Index', function () {
    it('mengizinkan akses index warehouse dengan permission ViewWarehouseStockOpname', function () {
        $this->fullRole->syncPermissions([InventoryPermission::ViewWarehouseStockOpname->value]);
        $response = $this->actingAs($this->testUser)->get('/inventory/stock-opname/warehouse');
        $response->assertOk();
    });
});

// ============================================
// CRUD OPERATIONS
// ============================================

describe('CRUD Operations', function () {
    
    it('store gudang menyimpan dengan division_id null dan status Pending', function () {
        $response = $this->actingAs($this->testUser)->post('/inventory/stock-opname/warehouse/store', [
            'opname_date' => now()->format('Y-m-d'),
            'notes' => 'Test opname gudang',
        ]);
        
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

describe('Process - Simpan Draf', function () {
    
    it('simpan draf mengubah status ke Process', function () {
        $opname = StockOpname::factory()->create([
            'user_id' => $this->testUser->id,
            'division_id' => null,
            'status' => StockOpnameStatus::Pending,
        ]);
        
        $response = $this->actingAs($this->testUser)->post("/inventory/stock-opname/warehouse/{$opname->id}/process", [
            'items' => [
                ['item_id' => $this->warehouseItem->id, 'physical_stock' => 95],
            ],
        ]);
        
        $response->assertRedirect();
        
        $opname->refresh();
        expect($opname->status)->toBe(StockOpnameStatus::Proses);
    });
});

// ============================================
// PROCESS (Konfirmasi → Stock Opname)
// ============================================

describe('Process - Konfirmasi', function () {
    
    it('konfirmasi mengubah status ke Stock Opname', function () {
        $opname = StockOpname::factory()->create([
            'user_id' => $this->testUser->id,
            'division_id' => null,
            'status' => StockOpnameStatus::Pending,
        ]);
        
        $response = $this->actingAs($this->testUser)->post("/inventory/stock-opname/warehouse/{$opname->id}/process", [
            'items' => [
                ['item_id' => $this->warehouseItem->id, 'physical_stock' => 85],
            ],
            'confirm' => true,
        ]);
        
        $response->assertRedirect();
        
        $opname->refresh();
        expect($opname->status)->toBe(StockOpnameStatus::StockOpname);
    });
});

// ============================================
// DATATABLE
// ============================================

describe('Datatable', function () {
    it('datatable search berfungsi', function () {
        StockOpname::factory()->create([
            'user_id' => $this->testUser->id,
            'division_id' => null,
            'notes' => 'Opname tahunan gudang',
        ]);
        
        $response = $this->actingAs($this->testUser)->get('/inventory/stock-opname/datatable/all?search=tahunan');
        
        $response->assertOk();
        $data = $response->json();
        expect(count($data['data']))->toBe(1);
    });
});
