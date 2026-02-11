<?php

namespace Modules\Inventory\Tests\Feature;

use App\Models\User;
use Modules\Inventory\Models\CategoryItem;
use Modules\Inventory\Enums\InventoryPermission;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryInventoryTest extends TestCase
{
    use RefreshDatabase;

    protected $viewerUser;
    protected $managerUser;
    protected $unauthorizedUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::firstOrCreate(['name' => InventoryPermission::ViewCategory->value, 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => InventoryPermission::ManageCategory->value, 'guard_name' => 'web']);
        
        // Create roles
        $viewRole = Role::firstOrCreate(['name' => 'Viewer', 'guard_name' => 'web']);
        $viewRole->syncPermissions([InventoryPermission::ViewCategory->value]);

        $manageRole = Role::firstOrCreate(['name' => 'Manager', 'guard_name' => 'web']);
        $manageRole->syncPermissions([InventoryPermission::ManageCategory->value]);

        // Create users
        $this->viewerUser = User::factory()->create();
        $this->viewerUser->assignRole($viewRole);

        $this->managerUser = User::factory()->create();
        $this->managerUser->assignRole($manageRole);
        
        $this->unauthorizedUser = User::factory()->create();
    }

    /**
     * Requirement 1: /inventory/categories, /inventory/categories/datatable, 
     * /inventory/categories/print-excel harus bisa diakses jika memiliki permission 
     * "Lihat Data Kategori Barang Gudang" (lihat_kategori)
     */
    public function test_can_access_view_routes_with_view_permission()
    {
        $this->actingAs($this->viewerUser)->get('/inventory/categories')->assertOk();
        $this->actingAs($this->viewerUser)->get('/inventory/categories/datatable')->assertOk();
        $this->actingAs($this->viewerUser)->get('/inventory/categories/print-excel')->assertOk();

        // Security check for unauthorized
        $this->actingAs($this->unauthorizedUser)->get('/inventory/categories')->assertForbidden();
        $this->actingAs($this->unauthorizedUser)->get('/inventory/categories/datatable')->assertForbidden();
        $this->actingAs($this->unauthorizedUser)->get('/inventory/categories/print-excel')->assertForbidden();
    }

    /**
     * Requirement 2: /inventory/categories/create, /inventory/categories/{id}/edit, 
     * /inventory/categories/{id}/delete harus bisa diakses jika memiliki permission 
     * "Kelola Data Kategori Barang Gudang" (kelola_kategori)
     */
    public function test_can_access_manage_routes_with_manage_permission()
    {
        $category = CategoryItem::factory()->create();
        
        $this->actingAs($this->managerUser)->get('/inventory/categories/create')->assertOk();
        $this->actingAs($this->managerUser)->get("/inventory/categories/{$category->id}/edit")->assertOk();
        
        // Action tests
        $this->actingAs($this->managerUser)->delete("/inventory/categories/{$category->id}/delete")->assertRedirect();
        
        // Security check for only-viewer
        // Re-create category because it was deleted
        $category = CategoryItem::factory()->create();
        
        $this->actingAs($this->viewerUser)->get('/inventory/categories/create')->assertForbidden();
        $this->actingAs($this->viewerUser)->get("/inventory/categories/{$category->id}/edit")->assertForbidden();
        $this->actingAs($this->viewerUser)->delete("/inventory/categories/{$category->id}/delete")->assertForbidden();
    }

    /**
     * Requirement 3: Pastikan form search datatable dapat mencari atribut name dan description
     */
    public function test_datatable_general_search_works_for_name_and_description()
    {
        CategoryItem::factory()->create(['name' => 'Electronic Device', 'description' => 'Gadgets']);
        CategoryItem::factory()->create(['name' => 'Furniture', 'description' => 'Wooden Tables']);
        
        // Search by name
        $response = $this->actingAs($this->viewerUser)->get('/inventory/categories/datatable?search=Electronic');
        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('data.0.name', 'Electronic Device');

        // Search by description
        $response = $this->actingAs($this->viewerUser)->get('/inventory/categories/datatable?search=Wooden');
        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('data.0.name', 'Furniture');
    }

    /**
     * Requirement 4: Pastikan Individual Search dapat bekerja untuk memfilter tiap kolom name atau description
     */
    public function test_datatable_individual_search_works()
    {
        CategoryItem::factory()->create(['name' => 'Indo Food', 'description' => 'Tasty']);
        CategoryItem::factory()->create(['name' => 'West Food', 'description' => 'Spicy']);
        
        // Individual name search
        $response = $this->actingAs($this->viewerUser)->get('/inventory/categories/datatable?name=Indo');
        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('data.0.name', 'Indo Food');

        // Individual description search
        $response = $this->actingAs($this->viewerUser)->get('/inventory/categories/datatable?description=Spicy');
        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('data.0.description', 'Spicy');
    }

    /**
     * Requirement 5: Pastikan fitur sort datatable berfungsi
     */
    public function test_datatable_sorting_works()
    {
        CategoryItem::factory()->create(['name' => 'Zebra', 'created_at' => now()->subDay()]);
        CategoryItem::factory()->create(['name' => 'Alpha', 'created_at' => now()]);
        
        // Sort by name ASC
        $response = $this->actingAs($this->viewerUser)->get('/inventory/categories/datatable?sort_by=name&sort_direction=asc');
        $response->assertJsonPath('data.0.name', 'Alpha');

        // Sort by name DESC
        $response = $this->actingAs($this->viewerUser)->get('/inventory/categories/datatable?sort_by=name&sort_direction=desc');
        $response->assertJsonPath('data.0.name', 'Zebra');
    }

    /**
     * Requirement 6: Pastikan fitur pagination berfungsi dengan baik
     */
    public function test_datatable_pagination_works()
    {
        // CategoryItemDatatableService uses paginate($request->limit ?? 20)
        CategoryItem::factory()->count(25)->create();
        
        $response = $this->actingAs($this->viewerUser)->get('/inventory/categories/datatable?page=2');
        $response->assertJsonPath('current_page', 2);
        $response->assertJsonPath('total', 25);
    }

    /**
     * Requirement 7: Pastikan Limit berfungsi dengan baik
     */
    public function test_datatable_limit_works()
    {
        CategoryItem::factory()->count(10)->create();
        
        $response = $this->actingAs($this->viewerUser)->get('/inventory/categories/datatable?limit=5');
        // Based on current_page logic, if total is 10, page 1 with limit 5 should have 5 items in 'data'
        $data = $response->json('data');
        $this->assertCount(5, $data);
        $response->assertJsonPath('per_page', 5);
    }

    /**
     * Requirement 8: Pastikan print mendownload file excel
     */
    public function test_print_excel_downloads_file()
    {
        $response = $this->actingAs($this->viewerUser)->get('/inventory/categories/print-excel');
        
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->assertHeader('Content-Disposition', 'attachment; filename="Data Kategori Barang Per '.date('d F Y').'.xlsx"');
    }
}
