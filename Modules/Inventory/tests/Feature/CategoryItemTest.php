<?php

use App\Models\User;
use Modules\Inventory\Models\CategoryItem;
use Modules\Inventory\Models\Item;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    // Create permissions
    Permission::firstOrCreate(['name' => \Modules\Inventory\Enums\InventoryPermission::ViewCategory->value, 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => \Modules\Inventory\Enums\InventoryPermission::ManageCategory->value, 'guard_name' => 'web']);
    
    // Create role with full permissions
    $this->testRole = Role::firstOrCreate(['name' => 'Test Role', 'guard_name' => 'web']);
    $this->testRole->syncPermissions([
        \Modules\Inventory\Enums\InventoryPermission::ViewCategory->value,
        \Modules\Inventory\Enums\InventoryPermission::ManageCategory->value,
    ]);
    
    // Create user
    $this->testUser = User::factory()->create();
    $this->testUser->assignRole($this->testRole);
});

// ============================================
// PERMISSION TESTS (EDIT, UPDATE, DELETE)
// ============================================

describe('Edit and Delete Permission', function () {
    
    /**
     * Memastikan user hanya dengan izin 'lihat' ditolak saat mengakses halaman edit.
     */
    it('denies edit page access without manage permission', function () {
        // User hanya dengan permission lihat
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ViewCategory->value]);
        
        $category = CategoryItem::factory()->create();
        
        $response = $this->actingAs($this->testUser)->get("/inventory/categories/{$category->id}/edit");
        
        $response->assertForbidden();
    });
    
    /**
     * Memastikan user dengan izin 'kelola' dapat mengakses halaman edit.
     */
    it('allows edit page access with manage permission', function () {
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ManageCategory->value]);
        
        $category = CategoryItem::factory()->create();
        
        $response = $this->actingAs($this->testUser)->get("/inventory/categories/{$category->id}/edit");
        
        $response->assertOk();
    });
    
    /**
     * Memastikan user tanpa izin 'kelola' tidak dapat menyimpan perubahan (update).
     */
    it('denies storing update without manage permission', function () {
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ViewCategory->value]);
        
        $category = CategoryItem::factory()->create();
        
        // Note: Route is /{categoryItem}/update
        $response = $this->actingAs($this->testUser)->put("/inventory/categories/{$category->id}/update", [
            'name' => 'Updated Name',
            'description' => 'Updated Description',
            'is_active' => true
        ]);
        
        $response->assertForbidden();
    });
    
    /**
     * Memastikan user dengan izin 'kelola' dapat menyimpan perubahan (update).
     */
    it('allows storing update with manage permission', function () {
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ManageCategory->value]);
        
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
    
    /**
     * Memastikan user tanpa izin 'kelola' ditolak saat menghapus kategori.
     */
    it('denies category deletion without manage permission', function () {
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ViewCategory->value]);
        
        $category = CategoryItem::factory()->create();
        
        // Note: Route is /{categoryItem}/delete
        $response = $this->actingAs($this->testUser)->delete("/inventory/categories/{$category->id}/delete");
        
        $response->assertForbidden();
    });
    
    /**
     * Memastikan user dengan izin 'kelola' dapat menghapus kategori.
     */
    it('allows category deletion with manage permission', function () {
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ManageCategory->value]);
        
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
    
    /**
     * Mengetes pembuatan kategori baru dengan input data yang valid.
     */
    it('can create category with valid data', function () {
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
    
    /**
     * Memastikan sistem menolak pendaftaran kategori dengan nama yang sudah terpakai.
     */
    it('fails to create category with duplicate name', function () {
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
    
    /**
     * Memastikan kategori yang ada dapat diperbarui datanya.
     */
    it('can update an existing category', function () {
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
    
    /**
     * Memastikan kategori yang belum berelasi dengan item dapat dihapus.
     */
    it('can delete an unused category', function () {
        $category = CategoryItem::factory()->create();
        
        // Note: Route is /{categoryItem}/delete
        $response = $this->actingAs($this->testUser)->delete("/inventory/categories/{$category->id}/delete");
        
        $response->assertRedirect();
        
        $this->assertDatabaseMissing('category_items', [
            'id' => $category->id
        ]);
    });
    
    /**
     * Memastikan integritas data: kategori yang masih punya item tidak boleh dihapus.
     */
    it('fails to delete category that is still used by items', function () {
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

describe('Validation', function () {
    
    /**
     * Memastikan field 'name' wajib diisi (required).
     */
    it('fails to create category without name', function () {
        // Note: Route is /store
        $response = $this->actingAs($this->testUser)->post('/inventory/categories/store', [
            'name' => '',
            'description' => 'Deskripsi',
            'is_active' => true
        ]);
        
        $response->assertSessionHasErrors('name');
    });
    
    /**
     * Memastikan field 'name' tidak boleh melebihi batas karakter database.
     */
    it('fails to create category with name exceeding character limit', function () {
        $longName = str_repeat('a', 256); // Assuming max is 255
        
        // Note: Route is /store
        $response = $this->actingAs($this->testUser)->post('/inventory/categories/store', [
            'name' => $longName,
            'description' => 'Deskripsi',
            'is_active' => true
        ]);
        
        $response->assertSessionHasErrors('name');
    });
    
    /**
     * Memastikan keunikan nama kategori (unique validation).
     */
    it('fails to create category with an already existing name', function () {
        CategoryItem::factory()->create(['name' => 'Existing Category']);
        
        // Note: Route is /store
        $response = $this->actingAs($this->testUser)->post('/inventory/categories/store', [
            'name' => 'Existing Category',
            'description' => 'New Description',
            'is_active' => true
        ]);
        
        $response->assertSessionHasErrors('name');
    });
    
    /**
     * Memastikan field 'description' bersifat opsional.
     */
    it('can create category without description', function () {
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
    
    /**
     * Memastikan fitur pagination datatable bekerja (current page, total data, etc).
     */
    it('handles pagination correctly', function () {
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
    
    /**
     * Memastikan filter pencarian pada datatable mengembalikan data yang relevan.
     */
    it('handles searching/filtering correctly', function () {
        CategoryItem::factory()->create(['name' => 'Elektronik']);
        CategoryItem::factory()->create(['name' => 'Furniture']);
        CategoryItem::factory()->create(['name' => 'Alat Tulis']);
        
        $response = $this->actingAs($this->testUser)->get('/inventory/categories/datatable?search=Elektronik');
        
        $response->assertOk();
        $data = $response->json();
        
        expect($data['total'])->toBe(1);
        expect($data['data'][0]['name'])->toBe('Elektronik');
    });
    
    /**
     * Memastikan request datatable tanpa filter mengembalikan seluruh data.
     */
    it('returns correct data structure from datatable', function () {
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

describe('Excel Export', function () {
    
    /**
     * Memastikan file yang diunduh memiliki mime type Excel yang valid.
     */
    it('exports a valid Excel file', function () {
        CategoryItem::factory()->count(5)->create();
        
        $response = $this->actingAs($this->testUser)->get('/inventory/categories/print-excel');
        
        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    });
    
    /**
     * Memastikan akses export ditolak jika user tidak punya izin 'lihat'.
     */
    it('respects view permission for export', function () {
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ManageCategory->value]); // Tanpa lihat
        
        CategoryItem::factory()->count(5)->create();
        
        $response = $this->actingAs($this->testUser)->get('/inventory/categories/print-excel');
        
        $response->assertForbidden();
    });
});
