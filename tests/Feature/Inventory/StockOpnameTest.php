<?php

use App\Models\Division;
use App\Models\User;
use Modules\Inventory\Enums\InventoryPermission;
use Modules\Inventory\Models\CategoryItem;
use Modules\Inventory\Models\Item;
use Modules\Inventory\Models\ItemTransaction;
use Modules\Inventory\Models\StockOpname;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    // Create all permissions
    $permissions = [
        'lihat_stock_opname_gudang',
        'lihat_stock_opname_divisi',
        'lihat_semua_stock_opname',
        'kelola_stock_opname_gudang',
        'kelola_stock_opname_divisi',
        'konfirmasi_stock_opname',
    ];
    
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
    $this->divisionItem = Item::factory()->create([
        'category_id' => $this->category->id,
        'division_id' => $this->division->id,
        'stock' => 50,
    ]);
});

// ============================================
// PERMISSION INDEX & DATATABLE
// ============================================

describe('Permission Index', function () {
    
    it('menolak akses index tanpa permission apapun', function () {
        $this->fullRole->syncPermissions([]);
        
        $response = $this->actingAs($this->testUser)->get('/inventory/stock-opname');
        
        $response->assertForbidden();
    });
    
    it('mengizinkan akses index dengan permission lihat_stock_opname_gudang', function () {
        $this->fullRole->syncPermissions(['lihat_stock_opname_gudang']);
        
        $response = $this->actingAs($this->testUser)->get('/inventory/stock-opname/warehouse');
        
        $response->assertOk();
    });
    
    it('mengizinkan akses index dengan permission lihat_semua_stock_opname', function () {
        $this->fullRole->syncPermissions(['lihat_semua_stock_opname']);
        
        $response = $this->actingAs($this->testUser)->get('/inventory/stock-opname/all');
        
        $response->assertOk();
    });
});

// ============================================
// PERMISSION CRUD GUDANG
// ============================================

describe('Permission CRUD Gudang', function () {
    
    it('menolak create gudang tanpa permission kelola_gudang', function () {
        $this->fullRole->syncPermissions(['lihat_stock_opname_gudang']);
        
        $response = $this->actingAs($this->testUser)->get('/inventory/stock-opname/warehouse/create');
        
        $response->assertForbidden();
    });
    
    it('mengizinkan create gudang dengan permission kelola_gudang', function () {
        $this->fullRole->syncPermissions(['kelola_stock_opname_gudang']);
        
        $response = $this->actingAs($this->testUser)->get('/inventory/stock-opname/warehouse/create');
        
        $response->assertOk();
    });
    
    it('menolak store gudang tanpa permission kelola_gudang', function () {
        $this->fullRole->syncPermissions(['lihat_stock_opname_gudang']);
        
        $response = $this->actingAs($this->testUser)->post('/inventory/stock-opname/warehouse/store', [
            'opname_date' => now()->format('Y-m-d'),
            'items' => [
                ['item_id' => $this->warehouseItem->id, 'physical_stock' => 95],
            ],
        ]);
        
        $response->assertForbidden();
    });
    
    it('mengizinkan store gudang dengan permission kelola_gudang', function () {
        $this->fullRole->syncPermissions(['kelola_stock_opname_gudang']);
        
        $response = $this->actingAs($this->testUser)->post('/inventory/stock-opname/warehouse/store', [
            'opname_date' => now()->format('Y-m-d'),
            'items' => [
                ['item_id' => $this->warehouseItem->id, 'physical_stock' => 95],
            ],
        ]);
        
        $response->assertRedirect();
        
        $this->assertDatabaseHas('stock_opnames', [
            'user_id' => $this->testUser->id,
            'division_id' => null,
            'status' => 'Draft',
        ]);
    });
});

// ============================================
// PERMISSION CRUD DIVISI
// ============================================

describe('Permission CRUD Divisi', function () {
    
    it('menolak create divisi tanpa permission kelola_divisi', function () {
        $this->fullRole->syncPermissions(['lihat_stock_opname_divisi']);
        
        $response = $this->actingAs($this->testUser)->get('/inventory/stock-opname/division/create');
        
        $response->assertForbidden();
    });
    
    it('mengizinkan create divisi dengan permission kelola_divisi', function () {
        $this->fullRole->syncPermissions(['kelola_stock_opname_divisi']);
        
        $response = $this->actingAs($this->testUser)->get('/inventory/stock-opname/division/create');
        
        $response->assertOk();
    });
});

// ============================================
// PERMISSION KONFIRMASI
// ============================================

describe('Permission Konfirmasi', function () {
    
    it('menolak konfirmasi tanpa permission konfirmasi_stock_opname', function () {
        $this->fullRole->syncPermissions(['kelola_stock_opname_gudang']);
        
        $opname = StockOpname::factory()->create([
            'user_id' => $this->testUser->id,
            'division_id' => null,
            'status' => 'Draft',
        ]);
        
        $response = $this->actingAs($this->testUser)->post("/inventory/stock-opname/{$opname->id}/confirm");
        
        $response->assertForbidden();
    });
    
    it('mengizinkan konfirmasi dengan permission konfirmasi_stock_opname', function () {
        $this->fullRole->syncPermissions(['konfirmasi_stock_opname', 'lihat_stock_opname_gudang']);
        
        $opname = StockOpname::factory()->create([
            'user_id' => $this->testUser->id,
            'division_id' => null,
            'status' => 'Draft',
        ]);
        $opname->items()->create([
            'item_id' => $this->warehouseItem->id,
            'system_stock' => 100,
            'physical_stock' => 95,
            'difference' => -5,
        ]);
        
        $response = $this->actingAs($this->testUser)->post("/inventory/stock-opname/{$opname->id}/confirm");
        
        $response->assertRedirect();
        
        $opname->refresh();
        expect($opname->status)->toBe('Confirmed');
    });
});

// ============================================
// CRUD OPERATIONS
// ============================================

describe('CRUD Operations', function () {
    
    it('dapat membuat stock opname gudang dengan data valid', function () {
        $response = $this->actingAs($this->testUser)->post('/inventory/stock-opname/warehouse/store', [
            'opname_date' => now()->format('Y-m-d'),
            'notes' => 'Test opname',
            'items' => [
                ['item_id' => $this->warehouseItem->id, 'physical_stock' => 90],
            ],
        ]);
        
        $response->assertRedirect();
        
        $this->assertDatabaseHas('stock_opnames', [
            'user_id' => $this->testUser->id,
            'division_id' => null,
            'notes' => 'Test opname',
        ]);
    });
    
    it('dapat membuat stock opname divisi dengan data valid', function () {
        $response = $this->actingAs($this->testUser)->post('/inventory/stock-opname/division/store', [
            'opname_date' => now()->format('Y-m-d'),
            'notes' => 'Test opname divisi',
            'items' => [
                ['item_id' => $this->divisionItem->id, 'physical_stock' => 45],
            ],
        ]);
        
        $response->assertRedirect();
        
        $this->assertDatabaseHas('stock_opnames', [
            'user_id' => $this->testUser->id,
            'division_id' => $this->division->id,
            'notes' => 'Test opname divisi',
        ]);
    });
    
    it('dapat mengupdate stock opname yang masih Draft', function () {
        $opname = StockOpname::factory()->create([
            'user_id' => $this->testUser->id,
            'division_id' => null,
            'status' => 'Draft',
            'notes' => 'Old notes',
        ]);
        $opname->items()->create([
            'item_id' => $this->warehouseItem->id,
            'system_stock' => 100,
            'physical_stock' => 95,
            'difference' => -5,
        ]);
        
        $response = $this->actingAs($this->testUser)->put("/inventory/stock-opname/warehouse/{$opname->id}/update", [
            'opname_date' => now()->format('Y-m-d'),
            'notes' => 'Updated notes',
            'items' => [
                ['item_id' => $this->warehouseItem->id, 'physical_stock' => 88],
            ],
        ]);
        
        $response->assertRedirect();
        
        $opname->refresh();
        expect($opname->notes)->toBe('Updated notes');
    });
    
    it('dapat menghapus stock opname yang masih Draft', function () {
        $opname = StockOpname::factory()->create([
            'user_id' => $this->testUser->id,
            'division_id' => null,
            'status' => 'Draft',
        ]);
        
        $response = $this->actingAs($this->testUser)->delete("/inventory/stock-opname/warehouse/{$opname->id}/delete");
        
        $response->assertRedirect();
        
        $this->assertDatabaseMissing('stock_opnames', ['id' => $opname->id]);
    });
});

// ============================================
// KONFIRMASI & STOK UPDATE
// ============================================

describe('Konfirmasi dan Stok Update', function () {
    
    it('konfirmasi mengupdate stok item ke physical_stock', function () {
        $this->fullRole->syncPermissions(['konfirmasi_stock_opname', 'lihat_stock_opname_gudang']);
        
        $opname = StockOpname::factory()->create([
            'user_id' => $this->testUser->id,
            'division_id' => null,
            'status' => 'Draft',
        ]);
        $opname->items()->create([
            'item_id' => $this->warehouseItem->id,
            'system_stock' => 100,
            'physical_stock' => 80,
            'difference' => -20,
        ]);
        
        $this->actingAs($this->testUser)->post("/inventory/stock-opname/{$opname->id}/confirm");
        
        $this->warehouseItem->refresh();
        expect($this->warehouseItem->stock)->toBe(80);
    });
    
    it('konfirmasi membuat transaksi untuk perbedaan stok', function () {
        $this->fullRole->syncPermissions(['konfirmasi_stock_opname', 'lihat_stock_opname_gudang']);
        
        $opname = StockOpname::factory()->create([
            'user_id' => $this->testUser->id,
            'division_id' => null,
            'status' => 'Draft',
        ]);
        $opname->items()->create([
            'item_id' => $this->warehouseItem->id,
            'system_stock' => 100,
            'physical_stock' => 85,
            'difference' => -15,
        ]);
        
        $this->actingAs($this->testUser)->post("/inventory/stock-opname/{$opname->id}/confirm");
        
        $transaction = ItemTransaction::where('item_id', $this->warehouseItem->id)->first();
        expect($transaction)->not->toBeNull();
        expect($transaction->quantity)->toBe(15);
    });
    
    it('gagal konfirmasi ulang opname yang sudah Confirmed', function () {
        $this->fullRole->syncPermissions(['konfirmasi_stock_opname', 'lihat_stock_opname_gudang']);
        
        $opname = StockOpname::factory()->create([
            'user_id' => $this->testUser->id,
            'division_id' => null,
            'status' => 'Confirmed',
        ]);
        
        $response = $this->actingAs($this->testUser)->post("/inventory/stock-opname/{$opname->id}/confirm");
        
        // Should have error in session or redirect back
        $response->assertSessionHas('error');
    });
});

// ============================================
// VALIDASI
// ============================================

describe('Validasi', function () {
    
    it('gagal store tanpa items', function () {
        $response = $this->actingAs($this->testUser)->post('/inventory/stock-opname/warehouse/store', [
            'opname_date' => now()->format('Y-m-d'),
            'items' => [],
        ]);
        
        $response->assertSessionHasErrors('items');
    });
    
    it('gagal store dengan physical_stock negatif', function () {
        $response = $this->actingAs($this->testUser)->post('/inventory/stock-opname/warehouse/store', [
            'opname_date' => now()->format('Y-m-d'),
            'items' => [
                ['item_id' => $this->warehouseItem->id, 'physical_stock' => -5],
            ],
        ]);
        
        $response->assertSessionHasErrors('items.0.physical_stock');
    });
    
    it('gagal store tanpa opname_date', function () {
        $response = $this->actingAs($this->testUser)->post('/inventory/stock-opname/warehouse/store', [
            'items' => [
                ['item_id' => $this->warehouseItem->id, 'physical_stock' => 90],
            ],
        ]);
        
        $response->assertSessionHasErrors('opname_date');
    });
});

// ============================================
// DATATABLE
// ============================================

describe('Datatable', function () {
    
    it('datatable mengembalikan data dengan benar', function () {
        StockOpname::factory()->count(5)->create([
            'user_id' => $this->testUser->id,
            'division_id' => null,
        ]);
        
        $response = $this->actingAs($this->testUser)->get('/inventory/stock-opname/datatable/warehouse');
        
        $response->assertOk();
        
        $data = $response->json();
        expect($data)->toHaveKey('data');
        expect(count($data['data']))->toBeGreaterThan(0);
    });
});

// ============================================
// EXPORT EXCEL
// ============================================

describe('Export Excel', function () {
    
    it('export dapat diakses dengan permission yang benar', function () {
        // Need lihat permission to access print-excel
        $this->fullRole->syncPermissions(['lihat_stock_opname_gudang']);
        
        StockOpname::factory()->count(3)->create([
            'user_id' => $this->testUser->id,
            'division_id' => null,
        ]);
        
        $response = $this->actingAs($this->testUser)->get('/inventory/stock-opname/print-excel/warehouse');
        
        // Should return OK status (Excel or redirect)
        $response->assertSuccessful();
    });
});
