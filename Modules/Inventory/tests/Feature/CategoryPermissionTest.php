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
    
    it('denies access to category index without any category permission', function () {
        // User tanpa permission kategori
        $this->testRole->syncPermissions([]);
        
        $response = $this->actingAs($this->testUser)->get('/inventory/categories');
        
        $response->assertForbidden();
    });
    
    it('allows access to category index with lihat permission', function () {
        // User dengan permission lihat
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ViewCategory->value]);
        
        $response = $this->actingAs($this->testUser)->get('/inventory/categories');
        
        $response->assertOk();
    });
    
    it('allows access to category index with kelola permission', function () {
        // User dengan permission kelola (tanpa lihat)
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ManageCategory->value]);
        
        $response = $this->actingAs($this->testUser)->get('/inventory/categories');
        
        $response->assertOk();
    });
    
    it('denies access to datatable without lihat permission', function () {
        // User HANYA dengan kelola, TANPA lihat
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ManageCategory->value]);
        
        $response = $this->actingAs($this->testUser)->get('/inventory/categories/datatable');
        
        $response->assertForbidden();
    });
    
    it('allows access to datatable with lihat permission', function () {
        // User dengan permission lihat
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ViewCategory->value]);
        
        $response = $this->actingAs($this->testUser)->get('/inventory/categories/datatable');
        
        $response->assertOk();
    });
    
    it('denies access to print-excel without lihat permission', function () {
        // User HANYA dengan kelola, TANPA lihat
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ManageCategory->value]);
        
        $response = $this->actingAs($this->testUser)->get('/inventory/categories/print-excel');
        
        $response->assertForbidden();
    });
    
    it('allows access to print-excel with lihat permission', function () {
        // User dengan permission lihat
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ViewCategory->value]);
        
        $response = $this->actingAs($this->testUser)->get('/inventory/categories/print-excel');
        
        $response->assertOk();
    });
    
    it('denies access to create page without kelola permission', function () {
        // User HANYA dengan lihat, TANPA kelola
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ViewCategory->value]);
        
        $response = $this->actingAs($this->testUser)->get('/inventory/categories/create');
        
        $response->assertForbidden();
    });
    
    it('allows access to create page with kelola permission', function () {
        // User dengan permission kelola
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ManageCategory->value]);
        
        $response = $this->actingAs($this->testUser)->get('/inventory/categories/create');
        
        $response->assertOk();
    });
    
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
    
    it('passes correct permissions to frontend when user has lihat only', function () {
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
    
    it('passes correct permissions to frontend when user has kelola only', function () {
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
