<?php

use App\Models\User;
use Modules\Inventory\Models\CategoryItem;
use Modules\Inventory\Models\Item;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    // Create permissions
    Permission::firstOrCreate(['name' => 'lihat_kategori_barang', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'kelola_kategori_barang', 'guard_name' => 'web']);
    
    // Create role with full permissions
    $this->testRole = Role::firstOrCreate(['name' => 'Test Role', 'guard_name' => 'web']);
    $this->testRole->syncPermissions(['lihat_kategori_barang', 'kelola_kategori_barang']);
    
    // Create user
    $this->testUser = User::factory()->create();
    $this->testUser->assignRole($this->testRole);
});

// ============================================
// PERMISSION TESTS (EDIT, UPDATE, DELETE)
// ============================================

describe('Permission untuk Edit dan Delete', function () {
    
    it('menolak akses halaman edit tanpa permission kelola', function () {
        // User hanya dengan permission lihat
        $this->testRole->syncPermissions(['lihat_kategori_barang']);
        
        $category = CategoryItem::factory()->create();
        
        $response = $this->actingAs($this->testUser)->get("/inventory/categories/{$category->id}/edit");
        
        $response->assertForbidden();
    });
    
    it('mengizinkan akses halaman edit dengan permission kelola', function () {
        $this->testRole->syncPermissions(['kelola_kategori_barang']);
        
        $category = CategoryItem::factory()->create();
        
        $response = $this->actingAs($this->testUser)->get("/inventory/categories/{$category->id}/edit");
        
        $response->assertOk();
    });
    
    it('menolak menyimpan update tanpa permission kelola', function () {
        $this->testRole->syncPermissions(['lihat_kategori_barang']);
        
        $category = CategoryItem::factory()->create();
        
        // Note: Route is /{categoryItem}/update
        $response = $this->actingAs($this->testUser)->put("/inventory/categories/{$category->id}/update", [
            'name' => 'Updated Name',
            'description' => 'Updated Description',
            'is_active' => true
        ]);
        
        $response->assertForbidden();
    });
    
    it('mengizinkan menyimpan update dengan permission kelola', function () {
        $this->testRole->syncPermissions(['kelola_kategori_barang']);
        
        $category = CategoryItem::factory()->create(['name' => 'Old Name']);
        
        // Note: Route is /{categoryItem}/update
        $response = $this->actingAs($this->testUser)->put("/inventory/categories/{$category->id}/update", [
            'name' => 'Updated Name',
            'description' => 'Updated Description',
            'is_active' => true
        ]);
        
        $response->assertRedirect();
        
        $this->assertDatabaseHas('category_items', [
            'id' => $category->id,
            'name' => 'Updated Name'
        ]);
    });
    
    it('menolak menghapus kategori tanpa permission kelola', function () {
        $this->testRole->syncPermissions(['lihat_kategori_barang']);
        
        $category = CategoryItem::factory()->create();
        
        // Note: Route is /{categoryItem}/delete
        $response = $this->actingAs($this->testUser)->delete("/inventory/categories/{$category->id}/delete");
        
        $response->assertForbidden();
    });
    
    it('mengizinkan menghapus kategori dengan permission kelola', function () {
        $this->testRole->syncPermissions(['kelola_kategori_barang']);
        
        $category = CategoryItem::factory()->create();
        
        // Note: Route is /{categoryItem}/delete
        $response = $this->actingAs($this->testUser)->delete("/inventory/categories/{$category->id}/delete");
        
        $response->assertRedirect();
        
        $this->assertDatabaseMissing('category_items', [
            'id' => $category->id
        ]);
    });
});

// ============================================
// CRUD OPERATIONS
// ============================================

describe('CRUD Operations', function () {
    
    it('dapat membuat kategori dengan data valid', function () {
        // Note: Route is /store
        $response = $this->actingAs($this->testUser)->post('/inventory/categories/store', [
            'name' => 'Kategori Baru',
            'description' => 'Deskripsi kategori baru',
            'is_active' => true
        ]);
        
        $response->assertRedirect();
        
        $this->assertDatabaseHas('category_items', [
            'name' => 'Kategori Baru',
            'description' => 'Deskripsi kategori baru',
            'is_active' => true
        ]);
    });
    
    it('gagal membuat kategori dengan nama duplikat', function () {
        // Buat kategori pertama
        CategoryItem::factory()->create(['name' => 'Kategori Existing']);
        
        // Coba buat dengan nama sama - Note: Route is /store
        $response = $this->actingAs($this->testUser)->post('/inventory/categories/store', [
            'name' => 'Kategori Existing',
            'description' => 'Deskripsi',
            'is_active' => true
        ]);
        
        $response->assertSessionHasErrors('name');
    });
    
    it('dapat mengupdate kategori yang ada', function () {
        $category = CategoryItem::factory()->create([
            'name' => 'Old Name',
            'description' => 'Old Description'
        ]);
        
        // Note: Route is /{categoryItem}/update
        $response = $this->actingAs($this->testUser)->put("/inventory/categories/{$category->id}/update", [
            'name' => 'New Name',
            'description' => 'New Description',
            'is_active' => false
        ]);
        
        $response->assertRedirect();
        
        $this->assertDatabaseHas('category_items', [
            'id' => $category->id,
            'name' => 'New Name',
            'description' => 'New Description',
            'is_active' => false
        ]);
    });
    
    it('dapat menghapus kategori yang tidak digunakan', function () {
        $category = CategoryItem::factory()->create();
        
        // Note: Route is /{categoryItem}/delete
        $response = $this->actingAs($this->testUser)->delete("/inventory/categories/{$category->id}/delete");
        
        $response->assertRedirect();
        
        $this->assertDatabaseMissing('category_items', [
            'id' => $category->id
        ]);
    });
    
    it('gagal menghapus kategori yang masih digunakan oleh barang', function () {
        $category = CategoryItem::factory()->create();
        
        // Buat item yang menggunakan kategori ini
        Item::factory()->create(['category_id' => $category->id]);
        
        // Note: Route is /{categoryItem}/delete
        $response = $this->actingAs($this->testUser)->delete("/inventory/categories/{$category->id}/delete");
        
        // Kategori masih ada di database karena ada FK constraint
        $this->assertDatabaseHas('category_items', ['id' => $category->id]);
        
        // Session should have errors
        $response->assertSessionHasErrors('delete');
    });
});

// ============================================
// VALIDATION
// ============================================

describe('Validasi', function () {
    
    it('gagal membuat kategori tanpa nama (required)', function () {
        // Note: Route is /store
        $response = $this->actingAs($this->testUser)->post('/inventory/categories/store', [
            'name' => '',
            'description' => 'Deskripsi',
            'is_active' => true
        ]);
        
        $response->assertSessionHasErrors('name');
    });
    
    it('gagal membuat kategori dengan nama melebihi batas karakter', function () {
        $longName = str_repeat('a', 256); // Assuming max is 255
        
        // Note: Route is /store
        $response = $this->actingAs($this->testUser)->post('/inventory/categories/store', [
            'name' => $longName,
            'description' => 'Deskripsi',
            'is_active' => true
        ]);
        
        $response->assertSessionHasErrors('name');
    });
    
    it('gagal membuat kategori dengan nama yang sudah ada (unique)', function () {
        CategoryItem::factory()->create(['name' => 'Existing Category']);
        
        // Note: Route is /store
        $response = $this->actingAs($this->testUser)->post('/inventory/categories/store', [
            'name' => 'Existing Category',
            'description' => 'New Description',
            'is_active' => true
        ]);
        
        $response->assertSessionHasErrors('name');
    });
    
    it('dapat membuat kategori tanpa deskripsi (optional)', function () {
        // Note: Route is /store
        $response = $this->actingAs($this->testUser)->post('/inventory/categories/store', [
            'name' => 'Kategori Tanpa Deskripsi',
            'is_active' => true
        ]);
        
        $response->assertRedirect();
        
        $this->assertDatabaseHas('category_items', [
            'name' => 'Kategori Tanpa Deskripsi',
            'description' => null
        ]);
    });
});

// ============================================
// DATATABLE
// ============================================

describe('Datatable', function () {
    
    it('pagination berfungsi dengan benar', function () {
        // Buat 25 kategori
        CategoryItem::factory()->count(25)->create();
        
        $response = $this->actingAs($this->testUser)->get('/inventory/categories/datatable?page=1');
        
        $response->assertOk();
        $response->assertJsonStructure([
            'data',
            'current_page',
            'last_page',
            'per_page',
            'total'
        ]);
        
        $data = $response->json();
        expect($data['current_page'])->toBe(1);
        expect($data['total'])->toBe(25);
        // Per page might be different from 10, just check data exists
        expect(count($data['data']))->toBeGreaterThan(0);
    });
    
    it('pencarian/filter berfungsi dengan benar', function () {
        CategoryItem::factory()->create(['name' => 'Elektronik']);
        CategoryItem::factory()->create(['name' => 'Furniture']);
        CategoryItem::factory()->create(['name' => 'Alat Tulis']);
        
        $response = $this->actingAs($this->testUser)->get('/inventory/categories/datatable?search=Elektronik');
        
        $response->assertOk();
        $data = $response->json();
        
        expect($data['total'])->toBe(1);
        expect($data['data'][0]['name'])->toBe('Elektronik');
    });
    
    it('datatable mengembalikan data dengan benar', function () {
        CategoryItem::factory()->create(['name' => 'Zebra']);
        CategoryItem::factory()->create(['name' => 'Alpha']);
        CategoryItem::factory()->create(['name' => 'Beta']);
        
        $response = $this->actingAs($this->testUser)->get('/inventory/categories/datatable');
        
        $response->assertOk();
        $data = $response->json();
        
        expect($data['total'])->toBe(3);
        expect(count($data['data']))->toBe(3);
    });
});

// ============================================
// EXPORT EXCEL
// ============================================

describe('Export Excel', function () {
    
    it('export mengembalikan file Excel yang valid', function () {
        CategoryItem::factory()->count(5)->create();
        
        $response = $this->actingAs($this->testUser)->get('/inventory/categories/print-excel');
        
        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    });
    
    it('export menghormati permission lihat', function () {
        $this->testRole->syncPermissions(['kelola_kategori_barang']); // Tanpa lihat
        
        CategoryItem::factory()->count(5)->create();
        
        $response = $this->actingAs($this->testUser)->get('/inventory/categories/print-excel');
        
        $response->assertForbidden();
    });
});
