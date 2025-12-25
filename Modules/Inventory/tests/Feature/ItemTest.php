<?php

use App\Models\User;
use Modules\Inventory\Models\CategoryItem;
use Modules\Inventory\Models\Item;
use Modules\Inventory\Models\ItemTransaction;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    // Create permissions
    Permission::firstOrCreate(['name' => \Modules\Inventory\Enums\InventoryPermission::ViewItem->value, 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => \Modules\Inventory\Enums\InventoryPermission::ManageItem->value, 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => \Modules\Inventory\Enums\InventoryPermission::IssueItemGudang->value, 'guard_name' => 'web']);
    
    // Create role with full permissions
    $this->testRole = Role::firstOrCreate(['name' => 'Test Role Items', 'guard_name' => 'web']);
    $this->testRole->syncPermissions([
        \Modules\Inventory\Enums\InventoryPermission::ViewItem->value,
        \Modules\Inventory\Enums\InventoryPermission::ManageItem->value,
        \Modules\Inventory\Enums\InventoryPermission::IssueItemGudang->value,
    ]);
    
    // Create user
    $this->testUser = User::factory()->create();
    $this->testUser->assignRole($this->testRole);
    
    // Create category for items
    $this->category = CategoryItem::factory()->create(['is_active' => true]);
});

// ============================================
// PERMISSION TESTS
// ============================================

describe('Permission Akses Index', function () {
    
    it('menolak akses index tanpa permission apapun', function () {
        $this->testRole->syncPermissions([]);
        
        $response = $this->actingAs($this->testUser)->get('/inventory/items');
        
        $response->assertForbidden();
    });
    
    it('mengizinkan akses index dengan permission lihat', function () {
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ViewItem->value]);
        
        $response = $this->actingAs($this->testUser)->get('/inventory/items');
        
        $response->assertOk();
    });
    
    it('mengizinkan akses index dengan permission kelola', function () {
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ManageItem->value]);
        
        $response = $this->actingAs($this->testUser)->get('/inventory/items');
        
        $response->assertOk();
    });
    
    it('mengizinkan akses index dengan permission keluarkan_stok', function () {
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::IssueItemGudang->value]);
        
        $response = $this->actingAs($this->testUser)->get('/inventory/items');
        
        $response->assertOk();
    });
});

describe('Permission CRUD', function () {
    
    it('menolak akses halaman create tanpa permission kelola', function () {
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ViewItem->value]);
        
        $response = $this->actingAs($this->testUser)->get('/inventory/items/create');
        
        $response->assertForbidden();
    });
    
    it('mengizinkan akses halaman create dengan permission kelola', function () {
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ManageItem->value]);
        
        $response = $this->actingAs($this->testUser)->get('/inventory/items/create');
        
        $response->assertOk();
    });
    
    it('menolak akses halaman edit tanpa permission kelola', function () {
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ViewItem->value]);
        
        $item = Item::factory()->create(['category_id' => $this->category->id]);
        
        $response = $this->actingAs($this->testUser)->get("/inventory/items/{$item->id}/edit");
        
        $response->assertForbidden();
    });
    
    it('mengizinkan akses halaman edit dengan permission kelola', function () {
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ManageItem->value]);
        
        $item = Item::factory()->create(['category_id' => $this->category->id]);
        
        $response = $this->actingAs($this->testUser)->get("/inventory/items/{$item->id}/edit");
        
        $response->assertOk();
    });
    
    it('menolak menyimpan update tanpa permission kelola', function () {
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ViewItem->value]);
        
        $item = Item::factory()->create(['category_id' => $this->category->id]);
        
        $response = $this->actingAs($this->testUser)->put("/inventory/items/{$item->id}/update", [
            'name' => 'Updated Name',
            'unit_of_measure' => 'pcs',
            'stock' => 10,
        ]);
        
        $response->assertForbidden();
    });
    
    it('menolak menghapus barang tanpa permission kelola', function () {
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ViewItem->value]);
        
        $item = Item::factory()->create(['category_id' => $this->category->id]);
        
        $response = $this->actingAs($this->testUser)->delete("/inventory/items/{$item->id}/delete");
        
        $response->assertForbidden();
    });
    
    it('mengizinkan menghapus barang dengan permission kelola', function () {
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ManageItem->value]);
        
        $item = Item::factory()->create(['category_id' => $this->category->id]);
        
        $response = $this->actingAs($this->testUser)->delete("/inventory/items/{$item->id}/delete");
        
        $response->assertRedirect();
        
        $this->assertDatabaseMissing('items', ['id' => $item->id]);
    });
});

describe('Permission Issue Stock', function () {
    
    it('menolak akses issue form tanpa permission keluarkan_stok', function () {
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ViewItem->value]);
        
        $item = Item::factory()->create(['category_id' => $this->category->id, 'stock' => 100]);
        
        $response = $this->actingAs($this->testUser)->get("/inventory/items/{$item->id}/issue");
        
        $response->assertForbidden();
    });
    
    it('mengizinkan akses issue form dengan permission keluarkan_stok', function () {
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::IssueItemGudang->value]);
        
        $item = Item::factory()->create(['category_id' => $this->category->id, 'stock' => 100]);
        
        $response = $this->actingAs($this->testUser)->get("/inventory/items/{$item->id}/issue");
        
        $response->assertOk();
    });
    
    it('menolak issue stok tanpa permission keluarkan_stok', function () {
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ViewItem->value]);
        
        $item = Item::factory()->create(['category_id' => $this->category->id, 'stock' => 100]);
        
        $response = $this->actingAs($this->testUser)->post("/inventory/items/{$item->id}/issue", [
            'quantity' => 10,
            'description' => 'Test issue',
        ]);
        
        $response->assertForbidden();
    });
    
    it('mengizinkan issue stok dengan permission keluarkan_stok', function () {
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::IssueItemGudang->value]);
        
        $item = Item::factory()->create(['category_id' => $this->category->id, 'stock' => 100]);
        
        $response = $this->actingAs($this->testUser)->post("/inventory/items/{$item->id}/issue", [
            'quantity' => 10,
            'description' => 'Test issue',
        ]);
        
        $response->assertRedirect();
        
        $item->refresh();
        expect($item->stock)->toBe(90);
    });
});

// ============================================
// CRUD OPERATIONS
// ============================================

describe('CRUD Operations', function () {
    
    it('dapat membuat barang dengan data valid', function () {
        $response = $this->actingAs($this->testUser)->post('/inventory/items/store', [
            'name' => 'Barang Baru',
            'category_id' => $this->category->id,
            'unit_of_measure' => 'pcs',
            'stock' => 50,
            'description' => 'Deskripsi barang baru',
        ]);
        
        $response->assertRedirect();
        
        $this->assertDatabaseHas('items', [
            'name' => 'Barang Baru',
            'category_id' => $this->category->id,
            'unit_of_measure' => 'pcs',
            'stock' => 50,
        ]);
    });
    
    it('dapat mengupdate barang yang ada', function () {
        $item = Item::factory()->create([
            'category_id' => $this->category->id,
            'name' => 'Old Name',
            'stock' => 10,
        ]);
        
        $response = $this->actingAs($this->testUser)->put("/inventory/items/{$item->id}/update", [
            'name' => 'New Name',
            'category_id' => $this->category->id,
            'unit_of_measure' => 'box',
            'stock' => 25,
            'description' => 'Updated description',
        ]);
        
        $response->assertRedirect();
        
        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'name' => 'New Name',
            'stock' => 25,
        ]);
    });
    
    it('dapat menghapus barang', function () {
        $item = Item::factory()->create(['category_id' => $this->category->id]);
        
        $response = $this->actingAs($this->testUser)->delete("/inventory/items/{$item->id}/delete");
        
        $response->assertRedirect();
        
        $this->assertDatabaseMissing('items', ['id' => $item->id]);
    });
    
    it('dapat membuat barang dengan stok 0', function () {
        $response = $this->actingAs($this->testUser)->post('/inventory/items/store', [
            'name' => 'Barang Tanpa Stok',
            'category_id' => $this->category->id,
            'unit_of_measure' => 'pcs',
            'stock' => 0,
        ]);
        
        $response->assertRedirect();
        
        $this->assertDatabaseHas('items', [
            'name' => 'Barang Tanpa Stok',
            'stock' => 0,
        ]);
    });
});

// ============================================
// VALIDATION
// ============================================

describe('Validasi', function () {
    
    it('gagal membuat barang tanpa nama (required)', function () {
        $response = $this->actingAs($this->testUser)->post('/inventory/items/store', [
            'name' => '',
            'unit_of_measure' => 'pcs',
            'stock' => 10,
        ]);
        
        $response->assertSessionHasErrors('name');
    });
    
    it('gagal membuat barang tanpa unit of measure (required)', function () {
        $response = $this->actingAs($this->testUser)->post('/inventory/items/store', [
            'name' => 'Test Item',
            'unit_of_measure' => '',
            'stock' => 10,
        ]);
        
        $response->assertSessionHasErrors('unit_of_measure');
    });
    
    it('gagal membuat barang dengan stok negatif', function () {
        $response = $this->actingAs($this->testUser)->post('/inventory/items/store', [
            'name' => 'Test Item',
            'unit_of_measure' => 'pcs',
            'stock' => -10,
        ]);
        
        $response->assertSessionHasErrors('stock');
    });
    
    it('dapat membuat barang tanpa kategori (nullable)', function () {
        $response = $this->actingAs($this->testUser)->post('/inventory/items/store', [
            'name' => 'Barang Tanpa Kategori',
            'unit_of_measure' => 'pcs',
            'stock' => 10,
        ]);
        
        $response->assertRedirect();
        
        $this->assertDatabaseHas('items', [
            'name' => 'Barang Tanpa Kategori',
            'category_id' => null,
        ]);
    });
});

// ============================================
// ISSUE STOCK
// ============================================

describe('Issue Stock', function () {
    
    it('dapat mengeluarkan stok dengan jumlah valid', function () {
        $item = Item::factory()->create(['category_id' => $this->category->id, 'stock' => 100]);
        
        $response = $this->actingAs($this->testUser)->post("/inventory/items/{$item->id}/issue", [
            'quantity' => 20,
            'description' => 'Pengeluaran untuk divisi',
        ]);
        
        $response->assertRedirect();
        
        $item->refresh();
        expect($item->stock)->toBe(80);
    });
    
    it('gagal mengeluarkan stok melebihi stok tersedia', function () {
        $item = Item::factory()->create(['category_id' => $this->category->id, 'stock' => 50]);
        
        $response = $this->actingAs($this->testUser)->post("/inventory/items/{$item->id}/issue", [
            'quantity' => 100,
            'description' => 'Pengeluaran melebihi stok',
        ]);
        
        $response->assertSessionHasErrors('quantity');
        
        // Stock should not change
        $item->refresh();
        expect($item->stock)->toBe(50);
    });
    
    it('gagal mengeluarkan stok dengan jumlah 0', function () {
        $item = Item::factory()->create(['category_id' => $this->category->id, 'stock' => 100]);
        
        $response = $this->actingAs($this->testUser)->post("/inventory/items/{$item->id}/issue", [
            'quantity' => 0,
            'description' => 'Invalid issue',
        ]);
        
        $response->assertSessionHasErrors('quantity');
    });
    
    it('gagal mengeluarkan stok tanpa deskripsi', function () {
        $item = Item::factory()->create(['category_id' => $this->category->id, 'stock' => 100]);
        
        $response = $this->actingAs($this->testUser)->post("/inventory/items/{$item->id}/issue", [
            'quantity' => 10,
            'description' => '',
        ]);
        
        $response->assertSessionHasErrors('description');
    });
    
    it('membuat transaksi setelah issue stok', function () {
        $item = Item::factory()->create(['category_id' => $this->category->id, 'stock' => 100]);
        
        $response = $this->actingAs($this->testUser)->post("/inventory/items/{$item->id}/issue", [
            'quantity' => 25,
            'description' => 'Test transaksi',
        ]);
        
        $response->assertRedirect();
        
        $this->assertDatabaseHas('item_transactions', [
            'item_id' => $item->id,
            'quantity' => 25,
            'user_id' => $this->testUser->id,
        ]);
    });
});

// ============================================
// DATATABLE
// ============================================

describe('Datatable', function () {
    
    it('datatable mengembalikan data yang benar', function () {
        Item::factory()->count(5)->create(['category_id' => $this->category->id]);
        
        $response = $this->actingAs($this->testUser)->get('/inventory/items/datatable');
        
        $response->assertOk();
        
        $data = $response->json();
        // Just verify we got data back
        expect($data)->toHaveKey('data');
        expect(count($data['data']))->toBeGreaterThan(0);
    });
    
    it('pencarian/filter berfungsi dengan benar', function () {
        Item::factory()->create(['category_id' => $this->category->id, 'name' => 'Laptop Dell']);
        Item::factory()->create(['category_id' => $this->category->id, 'name' => 'Mouse Logitech']);
        Item::factory()->create(['category_id' => $this->category->id, 'name' => 'Keyboard Mechanical']);
        
        $response = $this->actingAs($this->testUser)->get('/inventory/items/datatable?search=Laptop');
        
        $response->assertOk();
        
        $data = $response->json();
        
        // Should only find Laptop Dell when searching
        expect($data)->toHaveKey('data');
        expect(count($data['data']))->toBeGreaterThan(0);
        
        // Check if search result contains Laptop
        $found = false;
        foreach ($data['data'] as $item) {
            if (str_contains($item['name'], 'Laptop')) {
                $found = true;
                break;
            }
        }
        expect($found)->toBeTrue();
    });
});

// ============================================
// EXPORT EXCEL
// ============================================

describe('Export Excel', function () {
    
    it('export mengembalikan file Excel yang valid', function () {
        Item::factory()->count(5)->create(['category_id' => $this->category->id]);
        
        $response = $this->actingAs($this->testUser)->get('/inventory/items/print-excel');
        
        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    });
});
