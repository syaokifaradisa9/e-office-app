<?php

namespace Modules\Ticketing\Tests\Feature;

use App\Models\User;
use App\Models\Division;
use Modules\Ticketing\Models\AssetCategory;
use Modules\Ticketing\Models\Checklist;
use Modules\Ticketing\Enums\TicketingPermission;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChecklistTest extends TestCase
{
    use RefreshDatabase;

    protected $viewUser;      // ViewChecklist only
    protected $manageUser;    // ManageChecklist + ViewChecklist
    protected $divisionUser;  // ViewChecklist + ViewAssetCategoryDivisi
    protected $allUser;       // ManageChecklist + ViewAllAssetCategory
    protected $noPermUser;
    protected $myDivision;
    protected $otherDivision;
    protected $myCategory;
    protected $otherCategory;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Permissions
        foreach (TicketingPermission::values() as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Divisions
        $this->myDivision = Division::factory()->create(['name' => 'My Division']);
        $this->otherDivision = Division::factory()->create(['name' => 'Other Division']);

        // Categories
        $this->myCategory = AssetCategory::factory()->create([
            'name' => 'Laptop',
            'division_id' => $this->myDivision->id,
        ]);
        $this->otherCategory = AssetCategory::factory()->create([
            'name' => 'Printer',
            'division_id' => $this->otherDivision->id,
        ]);

        // ViewChecklist only (no category-level permission → treated as divisi for category scope)
        $viewRole = Role::firstOrCreate(['name' => 'ViewChecklist', 'guard_name' => 'web']);
        $viewRole->syncPermissions([
            TicketingPermission::ViewChecklist->value,
            TicketingPermission::ViewAssetCategoryDivisi->value,
        ]);
        $this->viewUser = User::factory()->create(['division_id' => $this->myDivision->id]);
        $this->viewUser->assignRole($viewRole);

        // ManageChecklist + ViewAssetCategoryDivisi (bisa kelola, tapi hanya divisi sendiri)
        $manageRole = Role::firstOrCreate(['name' => 'ManageChecklist', 'guard_name' => 'web']);
        $manageRole->syncPermissions([
            TicketingPermission::ViewChecklist->value,
            TicketingPermission::ManageChecklist->value,
            TicketingPermission::ViewAssetCategoryDivisi->value,
        ]);
        $this->manageUser = User::factory()->create(['division_id' => $this->myDivision->id]);
        $this->manageUser->assignRole($manageRole);

        // ManageChecklist + ViewAllAssetCategory (full admin)
        $allRole = Role::firstOrCreate(['name' => 'AllChecklist', 'guard_name' => 'web']);
        $allRole->syncPermissions([
            TicketingPermission::ViewChecklist->value,
            TicketingPermission::ManageChecklist->value,
            TicketingPermission::ViewAllAssetCategory->value,
        ]);
        $this->allUser = User::factory()->create(['division_id' => $this->myDivision->id]);
        $this->allUser->assignRole($allRole);

        // Division-level view user (for cross-division tests)
        $divisionViewRole = Role::firstOrCreate(['name' => 'DivisionChecklist', 'guard_name' => 'web']);
        $divisionViewRole->syncPermissions([
            TicketingPermission::ViewChecklist->value,
            TicketingPermission::ManageChecklist->value,
            TicketingPermission::ViewAssetCategoryDivisi->value,
        ]);
        $this->divisionUser = User::factory()->create(['division_id' => $this->myDivision->id]);
        $this->divisionUser->assignRole($divisionViewRole);

        // No permission user
        $this->noPermUser = User::factory()->create(['division_id' => $this->myDivision->id]);
    }

    // =========================================================================
    // Helper: base URL for checklist routes
    // =========================================================================

    private function checklistUrl(AssetCategory $cat, string $suffix = ''): string
    {
        return "/ticketing/asset-categories/{$cat->id}/checklists{$suffix}";
    }

    // =========================================================================
    // Grup A: Permission & Access Control
    // =========================================================================

    /**
     * A1: ViewChecklist → bisa akses index & datatable.
     */
    public function test_view_user_can_access_index_and_datatable()
    {
        $this->actingAs($this->viewUser)
            ->get($this->checklistUrl($this->myCategory))
            ->assertOk();

        $this->actingAs($this->viewUser)
            ->get($this->checklistUrl($this->myCategory, '/datatable'))
            ->assertOk();
    }

    /**
     * A2: ManageChecklist → bisa akses create, edit, store, update, delete.
     */
    public function test_manage_user_can_access_manage_routes()
    {
        $checklist = Checklist::factory()->create([
            'asset_category_id' => $this->myCategory->id,
        ]);

        $this->actingAs($this->manageUser)
            ->get($this->checklistUrl($this->myCategory, '/create'))
            ->assertOk();

        $this->actingAs($this->manageUser)
            ->get($this->checklistUrl($this->myCategory, "/{$checklist->id}/edit"))
            ->assertOk();
    }

    /**
     * A3: User tanpa permission → 403.
     */
    public function test_no_permission_user_gets_403()
    {
        $this->actingAs($this->noPermUser)
            ->get($this->checklistUrl($this->myCategory))
            ->assertForbidden();

        $this->actingAs($this->noPermUser)
            ->get($this->checklistUrl($this->myCategory, '/datatable'))
            ->assertForbidden();

        $this->actingAs($this->noPermUser)
            ->get($this->checklistUrl($this->myCategory, '/create'))
            ->assertForbidden();
    }

    /**
     * A4: ViewAssetCategoryDivisi → bisa akses checklist kategori divisi sendiri.
     */
    public function test_division_user_can_access_own_division_checklist()
    {
        $this->actingAs($this->divisionUser)
            ->get($this->checklistUrl($this->myCategory))
            ->assertOk();

        $this->actingAs($this->divisionUser)
            ->get($this->checklistUrl($this->myCategory, '/datatable'))
            ->assertOk();

        $this->actingAs($this->divisionUser)
            ->get($this->checklistUrl($this->myCategory, '/create'))
            ->assertOk();

        $this->actingAs($this->divisionUser)
            ->get($this->checklistUrl($this->myCategory, '/print/excel'))
            ->assertOk();
    }

    /**
     * A5: ViewAssetCategoryDivisi → TIDAK bisa akses checklist kategori divisi lain → 403.
     */
    public function test_division_user_cannot_access_other_division_checklist()
    {
        // index
        $this->actingAs($this->divisionUser)
            ->get($this->checklistUrl($this->otherCategory))
            ->assertForbidden();

        // datatable
        $this->actingAs($this->divisionUser)
            ->get($this->checklistUrl($this->otherCategory, '/datatable'))
            ->assertForbidden();

        // create
        $this->actingAs($this->divisionUser)
            ->get($this->checklistUrl($this->otherCategory, '/create'))
            ->assertForbidden();

        // store
        $this->actingAs($this->divisionUser)
            ->post($this->checklistUrl($this->otherCategory, '/store'), [
                'label' => 'Injected Checklist',
            ])
            ->assertForbidden();

        // edit & update
        $checklist = Checklist::factory()->create([
            'asset_category_id' => $this->otherCategory->id,
        ]);

        $this->actingAs($this->divisionUser)
            ->get($this->checklistUrl($this->otherCategory, "/{$checklist->id}/edit"))
            ->assertForbidden();

        $this->actingAs($this->divisionUser)
            ->put($this->checklistUrl($this->otherCategory, "/{$checklist->id}/update"), [
                'label' => 'Hack Update',
            ])
            ->assertForbidden();

        // delete
        $this->actingAs($this->divisionUser)
            ->delete($this->checklistUrl($this->otherCategory, "/{$checklist->id}/delete"))
            ->assertForbidden();

        // print excel
        $this->actingAs($this->divisionUser)
            ->get($this->checklistUrl($this->otherCategory, '/print/excel'))
            ->assertForbidden();
    }

    /**
     * A6: ViewAllAssetCategory → bisa akses checklist semua kategori.
     */
    public function test_all_user_can_access_any_division_checklist()
    {
        // Akses kategori divisi sendiri
        $this->actingAs($this->allUser)
            ->get($this->checklistUrl($this->myCategory))
            ->assertOk();

        // Akses kategori divisi lain
        $this->actingAs($this->allUser)
            ->get($this->checklistUrl($this->otherCategory))
            ->assertOk();

        $this->actingAs($this->allUser)
            ->get($this->checklistUrl($this->otherCategory, '/datatable'))
            ->assertOk();

        $this->actingAs($this->allUser)
            ->get($this->checklistUrl($this->otherCategory, '/create'))
            ->assertOk();
    }

    // =========================================================================
    // Grup B: CRUD — Store
    // =========================================================================

    /**
     * B1: Store berhasil → data tersimpan dengan asset_category_id benar.
     */
    public function test_store_creates_checklist()
    {
        $payload = [
            'label' => 'Cek Baterai',
            'description' => 'Pastikan baterai dalam kondisi baik',
        ];

        $response = $this->actingAs($this->allUser)
            ->post($this->checklistUrl($this->myCategory, '/store'), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('checklists', [
            'asset_category_id' => $this->myCategory->id,
            'label' => 'Cek Baterai',
            'description' => 'Pastikan baterai dalam kondisi baik',
        ]);
    }

    /**
     * B2: Store → checklist terhubung ke asset category yang benar.
     */
    public function test_store_links_to_correct_category()
    {
        $payload = ['label' => 'Cek Kabel'];

        $this->actingAs($this->allUser)
            ->post($this->checklistUrl($this->myCategory, '/store'), $payload);

        $checklist = Checklist::where('label', 'Cek Kabel')->first();
        $this->assertNotNull($checklist);
        $this->assertEquals($this->myCategory->id, $checklist->asset_category_id);
    }

    // =========================================================================
    // Grup C: CRUD — Update
    // =========================================================================

    /**
     * C1: Update berhasil → label & description berubah.
     */
    public function test_update_changes_checklist_data()
    {
        $checklist = Checklist::factory()->create([
            'asset_category_id' => $this->myCategory->id,
            'label' => 'Old Label',
            'description' => 'Old Description',
        ]);

        $payload = [
            'label' => 'New Label',
            'description' => 'New Description',
        ];

        $response = $this->actingAs($this->allUser)
            ->put($this->checklistUrl($this->myCategory, "/{$checklist->id}/update"), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('checklists', [
            'id' => $checklist->id,
            'label' => 'New Label',
            'description' => 'New Description',
        ]);
    }

    // =========================================================================
    // Grup D: CRUD — Delete
    // =========================================================================

    /**
     * D1: Delete berhasil → data dihapus dari DB.
     */
    public function test_delete_removes_checklist()
    {
        $checklist = Checklist::factory()->create([
            'asset_category_id' => $this->myCategory->id,
        ]);

        $response = $this->actingAs($this->allUser)
            ->delete($this->checklistUrl($this->myCategory, "/{$checklist->id}/delete"));

        $response->assertRedirect();
        $this->assertDatabaseMissing('checklists', ['id' => $checklist->id]);
    }

    // =========================================================================
    // Grup E: Datatable — Search & Filter
    // =========================================================================

    /**
     * E1: Global search by label.
     */
    public function test_datatable_global_search_by_label()
    {
        Checklist::factory()->create([
            'asset_category_id' => $this->myCategory->id,
            'label' => 'Cek Baterai',
        ]);
        Checklist::factory()->create([
            'asset_category_id' => $this->myCategory->id,
            'label' => 'Cek Kabel',
        ]);

        $response = $this->actingAs($this->allUser)
            ->get($this->checklistUrl($this->myCategory, '/datatable?search=Baterai'));

        $response->assertOk();
        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('data.0.label', 'Cek Baterai');
    }

    /**
     * E2: Global search by description.
     */
    public function test_datatable_global_search_by_description()
    {
        Checklist::factory()->create([
            'asset_category_id' => $this->myCategory->id,
            'label' => 'Item A',
            'description' => 'Periksa kondisi fisik',
        ]);
        Checklist::factory()->create([
            'asset_category_id' => $this->myCategory->id,
            'label' => 'Item B',
            'description' => 'Cek software update',
        ]);

        $response = $this->actingAs($this->allUser)
            ->get($this->checklistUrl($this->myCategory, '/datatable?search=kondisi'));

        $response->assertOk();
        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('data.0.label', 'Item A');
    }

    /**
     * E3: Individual filter label.
     */
    public function test_datatable_individual_filter_label()
    {
        Checklist::factory()->create([
            'asset_category_id' => $this->myCategory->id,
            'label' => 'Kabel Power',
        ]);
        Checklist::factory()->create([
            'asset_category_id' => $this->myCategory->id,
            'label' => 'Layar LCD',
        ]);

        $response = $this->actingAs($this->allUser)
            ->get($this->checklistUrl($this->myCategory, '/datatable?label=Kabel'));

        $response->assertOk();
        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('data.0.label', 'Kabel Power');
    }

    /**
     * E4: Individual filter description.
     */
    public function test_datatable_individual_filter_description()
    {
        Checklist::factory()->create([
            'asset_category_id' => $this->myCategory->id,
            'label' => 'Check A',
            'description' => 'Cek suhu mesin',
        ]);
        Checklist::factory()->create([
            'asset_category_id' => $this->myCategory->id,
            'label' => 'Check B',
            'description' => 'Cek tekanan udara',
        ]);

        $response = $this->actingAs($this->allUser)
            ->get($this->checklistUrl($this->myCategory, '/datatable?description=suhu'));

        $response->assertOk();
        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('data.0.label', 'Check A');
    }

    /**
     * E5: Datatable scoped to asset category → checklist lain tidak tampil.
     */
    public function test_datatable_scoped_to_category()
    {
        // Checklist di myCategory
        Checklist::factory()->create([
            'asset_category_id' => $this->myCategory->id,
            'label' => 'My Checklist',
        ]);

        // Checklist di otherCategory (noise)
        Checklist::factory()->create([
            'asset_category_id' => $this->otherCategory->id,
            'label' => 'Other Checklist',
        ]);

        $response = $this->actingAs($this->allUser)
            ->get($this->checklistUrl($this->myCategory, '/datatable'));

        $response->assertOk();
        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('data.0.label', 'My Checklist');
    }

    // =========================================================================
    // Grup F: Datatable — Pagination, Limit, Sort
    // =========================================================================

    /**
     * F1: Limit menentukan jumlah data per halaman.
     */
    public function test_datatable_limit()
    {
        for ($i = 1; $i <= 5; $i++) {
            Checklist::factory()->create([
                'asset_category_id' => $this->myCategory->id,
                'label' => "Item {$i}",
            ]);
        }

        $response = $this->actingAs($this->allUser)
            ->get($this->checklistUrl($this->myCategory, '/datatable?limit=3'));

        $response->assertOk();
        $response->assertJsonPath('total', 5);
        $response->assertJsonPath('per_page', 3);
        $this->assertCount(3, $response->json('data'));
    }

    /**
     * F2: Pagination → halaman 2 menampilkan data yang benar.
     */
    public function test_datatable_pagination()
    {
        for ($i = 1; $i <= 5; $i++) {
            Checklist::factory()->create([
                'asset_category_id' => $this->myCategory->id,
                'label' => "Item {$i}",
            ]);
        }

        $response = $this->actingAs($this->allUser)
            ->get($this->checklistUrl($this->myCategory, '/datatable?limit=2&page=2'));

        $response->assertOk();
        $response->assertJsonPath('total', 5);
        $response->assertJsonPath('current_page', 2);
        $this->assertCount(2, $response->json('data'));
    }

    /**
     * F3: Sort by label ASC & DESC.
     */
    public function test_datatable_sort_by_label()
    {
        Checklist::factory()->create([
            'asset_category_id' => $this->myCategory->id,
            'label' => 'Zebra',
        ]);
        Checklist::factory()->create([
            'asset_category_id' => $this->myCategory->id,
            'label' => 'Alpha',
        ]);

        // ASC
        $response = $this->actingAs($this->allUser)
            ->get($this->checklistUrl($this->myCategory, '/datatable?sort_by=label&sort_direction=asc'));

        $response->assertOk();
        $response->assertJsonPath('data.0.label', 'Alpha');

        // DESC
        $response = $this->actingAs($this->allUser)
            ->get($this->checklistUrl($this->myCategory, '/datatable?sort_by=label&sort_direction=desc'));

        $response->assertOk();
        $response->assertJsonPath('data.0.label', 'Zebra');
    }

    /**
     * F4: Default sort = label ASC.
     */
    public function test_datatable_default_sort_label_asc()
    {
        Checklist::factory()->create([
            'asset_category_id' => $this->myCategory->id,
            'label' => 'Mesin',
        ]);
        Checklist::factory()->create([
            'asset_category_id' => $this->myCategory->id,
            'label' => 'Baterai',
        ]);

        $response = $this->actingAs($this->allUser)
            ->get($this->checklistUrl($this->myCategory, '/datatable'));

        $response->assertOk();
        // Default sort by label asc → Baterai first
        $response->assertJsonPath('data.0.label', 'Baterai');
    }

    /**
     * F5: Default limit = 10.
     */
    public function test_datatable_default_limit_10()
    {
        for ($i = 1; $i <= 12; $i++) {
            Checklist::factory()->create([
                'asset_category_id' => $this->myCategory->id,
                'label' => "Item {$i}",
            ]);
        }

        $response = $this->actingAs($this->allUser)
            ->get($this->checklistUrl($this->myCategory, '/datatable'));

        $response->assertOk();
        $response->assertJsonPath('per_page', 10);
        $this->assertCount(10, $response->json('data'));
    }

    /**
     * F6: Excel export → menghasilkan file XLSX.
     */
    public function test_print_excel_returns_xlsx()
    {
        Checklist::factory()->create([
            'asset_category_id' => $this->myCategory->id,
            'label' => 'Export Item',
        ]);

        $response = $this->actingAs($this->allUser)
            ->get($this->checklistUrl($this->myCategory, '/print/excel'));

        $response->assertOk();
        $contentType = $response->headers->get('Content-Type');
        $this->assertStringContainsString('spreadsheetml', $contentType);
    }

    // =========================================================================
    // Grup G: Request Validation
    // =========================================================================

    /**
     * G1: label wajib diisi.
     */
    public function test_store_validation_label_required()
    {
        $response = $this->actingAs($this->allUser)
            ->post($this->checklistUrl($this->myCategory, '/store'), []);

        $response->assertSessionHasErrors(['label']);
    }

    /**
     * G2: label max 255 karakter.
     */
    public function test_store_validation_label_max_255()
    {
        $response = $this->actingAs($this->allUser)
            ->post($this->checklistUrl($this->myCategory, '/store'), [
                'label' => str_repeat('A', 256),
            ]);

        $response->assertSessionHasErrors(['label']);
    }

    /**
     * G3: description max 1000 karakter.
     */
    public function test_store_validation_description_max_1000()
    {
        $response = $this->actingAs($this->allUser)
            ->post($this->checklistUrl($this->myCategory, '/store'), [
                'label' => 'Valid Label',
                'description' => str_repeat('A', 1001),
            ]);

        $response->assertSessionHasErrors(['description']);
    }
}
