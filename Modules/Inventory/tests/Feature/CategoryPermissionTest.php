<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    // Create permissions jika belum ada
    Permission::firstOrCreate(['name' => \Modules\Inventory\Enums\InventoryPermission::ViewCategory->value, 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => \Modules\Inventory\Enums\InventoryPermission::ManageCategory->value, 'guard_name' => 'web']);
    
    // Create role untuk test
    $this->testRole = Role::firstOrCreate(['name' => 'Test Role', 'guard_name' => 'web']);
    
    // Create user dengan role tersebut
    $this->testUser = User::factory()->create();
    $this->testUser->assignRole($this->testRole);
});

describe('Category Permission Access Control', function () {
    
    /**
     * Memastikan user dilarang mengakses halaman kategori jika tidak memiliki izin apapun.
     */
    it('denies access to category index without any category permission', function () {
        // User tanpa permission kategori
        $this->testRole->syncPermissions([]);
        
        $response = $this->actingAs($this->testUser)->get('/inventory/categories');
        
        $response->assertForbidden();
    });
    
    /**
     * Memastikan user dengan izin 'lihat_kategori_barang' diperbolehkan mengakses halaman index.
     */
    it('allows access to category index with view permission', function () {
        // User dengan permission lihat
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ViewCategory->value]);
        
        $response = $this->actingAs($this->testUser)->get('/inventory/categories');
        
        $response->assertOk();
    });
    
    /**
     * Memastikan user dengan izin 'kelola_kategori_barang' diperbolehkan mengakses halaman index.
     */
    it('allows access to category index with manage permission', function () {
        // User dengan permission kelola (tanpa lihat)
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ManageCategory->value]);
        
        $response = $this->actingAs($this->testUser)->get('/inventory/categories');
        
        $response->assertOk();
    });
    
    /**
     * Memastikan akses API datatable dilarang jika user tidak memiliki izin 'lihat'.
     */
    it('denies access to datatable without view permission', function () {
        // User HANYA dengan kelola, TANPA lihat
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ManageCategory->value]);
        
        $response = $this->actingAs($this->testUser)->get('/inventory/categories/datatable');
        
        $response->assertForbidden();
    });
    
    /**
     * Memastikan akses API datatable diperbolehkan bagi user dengan izin 'lihat'.
     */
    it('allows access to datatable with view permission', function () {
        // User dengan permission lihat
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ViewCategory->value]);
        
        $response = $this->actingAs($this->testUser)->get('/inventory/categories/datatable');
        
        $response->assertOk();
    });
    
    /**
     * Memastikan fitur print excel dilarang bagi user tanpa izin 'lihat'.
     */
    it('denies access to print-excel without view permission', function () {
        // User HANYA dengan kelola, TANPA lihat
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ManageCategory->value]);
        
        $response = $this->actingAs($this->testUser)->get('/inventory/categories/print-excel');
        
        $response->assertForbidden();
    });
    
    /**
     * Memastikan fitur print excel diperbolehkan bagi user dengan izin 'lihat'.
     */
    it('allows access to print-excel with view permission', function () {
        // User dengan permission lihat
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ViewCategory->value]);
        
        $response = $this->actingAs($this->testUser)->get('/inventory/categories/print-excel');
        
        $response->assertOk();
    });
    
    /**
     * Memastikan halaman tambah kategori dilarang bagi user tanpa izin 'kelola'.
     */
    it('denies access to create page without manage permission', function () {
        // User HANYA dengan lihat, TANPA kelola
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ViewCategory->value]);
        
        $response = $this->actingAs($this->testUser)->get('/inventory/categories/create');
        
        $response->assertForbidden();
    });
    
    /**
     * Memastikan halaman tambah kategori diperbolehkan bagi user dengan izin 'kelola'.
     */
    it('allows access to create page with manage permission', function () {
        // User dengan permission kelola
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ManageCategory->value]);
        
        $response = $this->actingAs($this->testUser)->get('/inventory/categories/create');
        
        $response->assertOk();
    });
    
    /**
     * Memastikan user dengan kedua izin (lihat & kelola) memiliki akses penuh ke seluruh fitur.
     */
    it('allows full access with both permissions', function () {
        // User dengan kedua permission
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ViewCategory->value, \Modules\Inventory\Enums\InventoryPermission::ManageCategory->value]);
        
        // Can access index
        $this->actingAs($this->testUser)->get('/inventory/categories')->assertOk();
        
        // Can access datatable
        $this->actingAs($this->testUser)->get('/inventory/categories/datatable')->assertOk();
        
        // Can access create
        $this->actingAs($this->testUser)->get('/inventory/categories/create')->assertOk();
    });
});

describe('Frontend Permission Props', function () {
    
    /**
     * Memastikan prop 'permissions' yang dikirim ke frontend sesuai (hanya 'lihat').
     */
    it('passes correct permissions to frontend when user has view only', function () {
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ViewCategory->value]);
        
        $response = $this->actingAs($this->testUser)->get('/inventory/categories');
        
        $response->assertOk();
        $response->assertInertia(fn ($page) => 
            $page->has('permissions')
                 ->where('permissions', function ($permissions) {
                     $permArray = is_array($permissions) ? $permissions : $permissions->toArray();
                     return in_array(\Modules\Inventory\Enums\InventoryPermission::ViewCategory->value, $permArray) 
                         && !in_array(\Modules\Inventory\Enums\InventoryPermission::ManageCategory->value, $permArray);
                 })
        );
    });
    
    /**
     * Memastikan prop 'permissions' yang dikirim ke frontend sesuai (hanya 'kelola').
     */
    it('passes correct permissions to frontend when user has manage only', function () {
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ManageCategory->value]);
        
        $response = $this->actingAs($this->testUser)->get('/inventory/categories');
        
        $response->assertOk();
        $response->assertInertia(fn ($page) => 
            $page->has('permissions')
                 ->where('permissions', function ($permissions) {
                     $permArray = is_array($permissions) ? $permissions : $permissions->toArray();
                     return !in_array(\Modules\Inventory\Enums\InventoryPermission::ViewCategory->value, $permArray) 
                         && in_array(\Modules\Inventory\Enums\InventoryPermission::ManageCategory->value, $permArray);
                 })
        );
    });
    
    /**
     * Memastikan prop 'permissions' yang dikirim ke frontend mencakup keduanya jika user berwenang.
     */
    it('passes both permissions to frontend when user has both', function () {
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ViewCategory->value, \Modules\Inventory\Enums\InventoryPermission::ManageCategory->value]);
        
        $response = $this->actingAs($this->testUser)->get('/inventory/categories');
        
        $response->assertOk();
        $response->assertInertia(fn ($page) => 
            $page->has('permissions')
                 ->where('permissions', function ($permissions) {
                     $permArray = is_array($permissions) ? $permissions : $permissions->toArray();
                     return in_array(\Modules\Inventory\Enums\InventoryPermission::ViewCategory->value, $permArray) 
                         && in_array(\Modules\Inventory\Enums\InventoryPermission::ManageCategory->value, $permArray);
                 })
        );
    });
});
