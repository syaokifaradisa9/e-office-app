<?php

namespace Modules\Ticketing\Tests\Feature;

use App\Models\User;
use App\Models\Division;
use Modules\Ticketing\Models\AssetCategory;
use Modules\Ticketing\Models\AssetItem;
use Modules\Ticketing\Models\Maintenance;
use Modules\Ticketing\Enums\TicketingPermission;
use Modules\Ticketing\Enums\AssetCategoryType;
use Modules\Ticketing\Enums\MaintenanceStatus;
use Modules\Ticketing\Services\AssetItemService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetItemTest extends TestCase
{
    use RefreshDatabase;

    protected $personalUser;
    protected $divisionUser;
    protected $allUser;
    protected $managerUser;
    protected $deleterUser;
    protected $noPermUser;
    protected $myDivision;
    protected $otherDivision;
    protected $category;

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

        // Category
        $this->category = AssetCategory::factory()->create([
            'division_id' => $this->myDivision->id,
            'maintenance_count' => 3,
        ]);

        // A1: ViewPersonalAsset + ManageAsset (untuk test store/update enforcement)
        $personalRole = Role::firstOrCreate(['name' => 'PersonalAsset', 'guard_name' => 'web']);
        $personalRole->syncPermissions([
            TicketingPermission::ViewPersonalAsset->value,
            TicketingPermission::ManageAsset->value,
        ]);
        $this->personalUser = User::factory()->create(['division_id' => $this->myDivision->id]);
        $this->personalUser->assignRole($personalRole);

        // A2: ViewDivisionAsset + ManageAsset
        $divisionRole = Role::firstOrCreate(['name' => 'DivisionAsset', 'guard_name' => 'web']);
        $divisionRole->syncPermissions([
            TicketingPermission::ViewDivisionAsset->value,
            TicketingPermission::ManageAsset->value,
        ]);
        $this->divisionUser = User::factory()->create(['division_id' => $this->myDivision->id]);
        $this->divisionUser->assignRole($divisionRole);

        // A3: ViewAllAsset
        $allRole = Role::firstOrCreate(['name' => 'AllAsset', 'guard_name' => 'web']);
        $allRole->syncPermissions([
            TicketingPermission::ViewAllAsset->value,
            TicketingPermission::ManageAsset->value,
            TicketingPermission::DeleteAsset->value,
        ]);
        $this->allUser = User::factory()->create(['division_id' => $this->myDivision->id]);
        $this->allUser->assignRole($allRole);

        // A4: ManageAsset (for create/edit/store/update)
        $managerRole = Role::firstOrCreate(['name' => 'ManagerAsset', 'guard_name' => 'web']);
        $managerRole->syncPermissions([
            TicketingPermission::ManageAsset->value,
        ]);
        $this->managerUser = User::factory()->create(['division_id' => $this->myDivision->id]);
        $this->managerUser->assignRole($managerRole);

        // A5: DeleteAsset
        $deleterRole = Role::firstOrCreate(['name' => 'DeleterAsset', 'guard_name' => 'web']);
        $deleterRole->syncPermissions([TicketingPermission::DeleteAsset->value]);
        $this->deleterUser = User::factory()->create(['division_id' => $this->myDivision->id]);
        $this->deleterUser->assignRole($deleterRole);

        // A6: No permission
        $this->noPermUser = User::factory()->create(['division_id' => $this->myDivision->id]);
    }

    // =========================================================================
    // Grup A: Permission & Access Control
    // =========================================================================

    /**
     * A1: ViewPersonalAsset → hanya lihat asset yang ter-assign ke user.
     */
    public function test_personal_user_only_sees_own_assets()
    {
        // Asset milik personalUser
        $myAsset = AssetItem::factory()->create([
            'asset_category_id' => $this->category->id,
            'division_id' => $this->myDivision->id,
            'merk' => 'My Asset',
        ]);
        $myAsset->users()->attach($this->personalUser->id);

        // Noise: asset milik orang lain
        $otherAsset = AssetItem::factory()->create([
            'asset_category_id' => $this->category->id,
            'division_id' => $this->myDivision->id,
            'merk' => 'Other Asset',
        ]);
        $otherUser = User::factory()->create(['division_id' => $this->myDivision->id]);
        $otherAsset->users()->attach($otherUser->id);

        $response = $this->actingAs($this->personalUser)
            ->get('/ticketing/assets/datatable');

        $response->assertOk();
        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('data.0.merk', 'My Asset');
    }

    /**
     * A2: ViewDivisionAsset → lihat semua asset di divisi sendiri.
     */
    public function test_division_user_sees_all_division_assets()
    {
        // Asset di divisi sendiri
        AssetItem::factory()->create([
            'asset_category_id' => $this->category->id,
            'division_id' => $this->myDivision->id,
            'merk' => 'Division Asset',
        ]);

        // Noise: asset di divisi lain
        $otherCategory = AssetCategory::factory()->create(['division_id' => $this->otherDivision->id]);
        AssetItem::factory()->create([
            'asset_category_id' => $otherCategory->id,
            'division_id' => $this->otherDivision->id,
            'merk' => 'Other Div Asset',
        ]);

        $response = $this->actingAs($this->divisionUser)
            ->get('/ticketing/assets/datatable');

        $response->assertOk();
        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('data.0.merk', 'Division Asset');
    }

    /**
     * A3: ViewAllAsset → lihat semua asset dari semua divisi.
     */
    public function test_all_user_sees_all_assets()
    {
        AssetItem::factory()->create([
            'asset_category_id' => $this->category->id,
            'division_id' => $this->myDivision->id,
        ]);
        $otherCategory = AssetCategory::factory()->create(['division_id' => $this->otherDivision->id]);
        AssetItem::factory()->create([
            'asset_category_id' => $otherCategory->id,
            'division_id' => $this->otherDivision->id,
        ]);

        $response = $this->actingAs($this->allUser)
            ->get('/ticketing/assets/datatable');

        $response->assertOk();
        $response->assertJsonPath('total', 2);
    }

    /**
     * A4: ManageAsset → bisa akses create dan edit routes.
     */
    public function test_manager_can_access_manage_routes()
    {
        $asset = AssetItem::factory()->create([
            'asset_category_id' => $this->category->id,
            'division_id' => $this->myDivision->id,
        ]);

        $this->actingAs($this->managerUser)->get('/ticketing/assets/create')->assertOk();
        $this->actingAs($this->managerUser)->get("/ticketing/assets/{$asset->id}/edit")->assertOk();
    }

    /**
     * A5: DeleteAsset → bisa akses delete route.
     */
    public function test_deleter_can_delete_asset()
    {
        $asset = AssetItem::factory()->create([
            'asset_category_id' => $this->category->id,
            'division_id' => $this->myDivision->id,
        ]);

        $response = $this->actingAs($this->deleterUser)
            ->delete("/ticketing/assets/{$asset->id}/delete");

        $response->assertRedirect();
        $this->assertSoftDeleted('asset_items', ['id' => $asset->id]);
    }

    /**
     * A6: User tanpa permission → 403 Forbidden.
     */
    public function test_no_permission_user_gets_403()
    {
        $this->actingAs($this->noPermUser)->get('/ticketing/assets')->assertForbidden();
        $this->actingAs($this->noPermUser)->get('/ticketing/assets/datatable')->assertForbidden();
        $this->actingAs($this->noPermUser)->get('/ticketing/assets/create')->assertForbidden();
    }

    // =========================================================================
    // Grup B: CRUD — Store
    // =========================================================================

    /**
     * B1: Store berhasil → data tersimpan di DB.
     */
    public function test_store_creates_asset_item()
    {
        $payload = [
            'asset_category_id' => $this->category->id,
            'merk' => 'Dell',
            'model' => 'XPS 15',
            'serial_number' => 'SN-UNIQUE-001',
            'division_id' => $this->myDivision->id,
            'another_attributes' => [['key' => 'RAM', 'value' => '16GB']],
        ];

        $response = $this->actingAs($this->allUser)->post('/ticketing/assets/store', $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('asset_items', [
            'merk' => 'Dell',
            'model' => 'XPS 15',
            'serial_number' => 'SN-UNIQUE-001',
        ]);
    }

    /**
     * B2: Store → user_ids tersimpan di pivot table.
     */
    public function test_store_syncs_user_ids()
    {
        $user1 = User::factory()->create(['division_id' => $this->myDivision->id]);
        $user2 = User::factory()->create(['division_id' => $this->myDivision->id]);

        $payload = [
            'asset_category_id' => $this->category->id,
            'merk' => 'HP',
            'model' => 'EliteBook',
            'division_id' => $this->myDivision->id,
            'user_ids' => [$user1->id, $user2->id],
        ];

        $this->actingAs($this->allUser)->post('/ticketing/assets/store', $payload);

        $asset = AssetItem::where('merk', 'HP')->first();
        $this->assertNotNull($asset);
        $this->assertEquals(2, $asset->users()->count());
        $this->assertTrue($asset->users->contains($user1));
        $this->assertTrue($asset->users->contains($user2));
    }

    /**
     * B3: Store → maintenance otomatis ter-generate.
     */
    public function test_store_generates_maintenances()
    {
        $payload = [
            'asset_category_id' => $this->category->id, // maintenance_count = 3
            'merk' => 'Lenovo',
            'model' => 'ThinkPad',
            'division_id' => $this->myDivision->id,
        ];

        $this->actingAs($this->allUser)->post('/ticketing/assets/store', $payload);

        $asset = AssetItem::where('merk', 'Lenovo')->first();
        $this->assertNotNull($asset);

        $pendingCount = $asset->maintenances()
            ->where('status', MaintenanceStatus::PENDING->value)
            ->count();

        $this->assertGreaterThan(0, $pendingCount);
    }

    /**
     * B4: Store → serial_number unique validation.
     */
    public function test_store_serial_number_must_be_unique()
    {
        AssetItem::factory()->create([
            'asset_category_id' => $this->category->id,
            'division_id' => $this->myDivision->id,
            'serial_number' => 'DUPLICATE-SN',
        ]);

        $payload = [
            'asset_category_id' => $this->category->id,
            'merk' => 'Test',
            'division_id' => $this->myDivision->id,
            'serial_number' => 'DUPLICATE-SN',
        ];

        $response = $this->actingAs($this->allUser)->post('/ticketing/assets/store', $payload);
        $response->assertSessionHasErrors(['serial_number']);
    }

    // =========================================================================
    // Grup C: CRUD — Update
    // =========================================================================

    /**
     * C1: Update berhasil → data berubah di DB.
     */
    public function test_update_changes_asset_data()
    {
        $asset = AssetItem::factory()->create([
            'asset_category_id' => $this->category->id,
            'division_id' => $this->myDivision->id,
            'merk' => 'Old Merk',
            'model' => 'Old Model',
        ]);

        $payload = [
            'asset_category_id' => $this->category->id,
            'merk' => 'New Merk',
            'model' => 'New Model',
            'division_id' => $this->myDivision->id,
        ];

        $response = $this->actingAs($this->allUser)
            ->put("/ticketing/assets/{$asset->id}/update", $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('asset_items', [
            'id' => $asset->id,
            'merk' => 'New Merk',
            'model' => 'New Model',
        ]);
    }

    /**
     * C2: Update → user_ids ter-sync ulang.
     */
    public function test_update_syncs_user_ids()
    {
        $user1 = User::factory()->create(['division_id' => $this->myDivision->id]);
        $user2 = User::factory()->create(['division_id' => $this->myDivision->id]);
        $user3 = User::factory()->create(['division_id' => $this->myDivision->id]);

        $asset = AssetItem::factory()->create([
            'asset_category_id' => $this->category->id,
            'division_id' => $this->myDivision->id,
        ]);
        $asset->users()->sync([$user1->id, $user2->id]);

        // Update: ganti ke user3 saja
        $payload = [
            'asset_category_id' => $this->category->id,
            'merk' => $asset->merk,
            'division_id' => $this->myDivision->id,
            'user_ids' => [$user3->id],
        ];

        $this->actingAs($this->allUser)
            ->put("/ticketing/assets/{$asset->id}/update", $payload);

        $asset->refresh();
        $this->assertEquals(1, $asset->users()->count());
        $this->assertTrue($asset->users->contains($user3));
        $this->assertFalse($asset->users->contains($user1));
    }

    /**
     * C3: Update → maintenance TIDAK di-regenerasi.
     */
    public function test_update_does_not_regenerate_maintenances()
    {
        $asset = AssetItem::factory()->create([
            'asset_category_id' => $this->category->id,
            'division_id' => $this->myDivision->id,
        ]);
        app(AssetItemService::class)->generateMaintenances($asset);

        $maintenanceIdsBefore = $asset->maintenances()->pluck('id')->toArray();

        $payload = [
            'asset_category_id' => $this->category->id,
            'merk' => 'Updated Merk',
            'model' => 'Updated Model',
            'division_id' => $this->myDivision->id,
        ];

        $this->actingAs($this->allUser)
            ->put("/ticketing/assets/{$asset->id}/update", $payload);

        $maintenanceIdsAfter = $asset->maintenances()->pluck('id')->toArray();
        $this->assertEquals($maintenanceIdsBefore, $maintenanceIdsAfter);
    }

    /**
     * C4: Update → serial_number milik sendiri → tidak error.
     */
    public function test_update_serial_number_same_as_own_is_ok()
    {
        $asset = AssetItem::factory()->create([
            'asset_category_id' => $this->category->id,
            'division_id' => $this->myDivision->id,
            'serial_number' => 'MY-OWN-SN',
        ]);

        $payload = [
            'asset_category_id' => $this->category->id,
            'merk' => 'Updated',
            'division_id' => $this->myDivision->id,
            'serial_number' => 'MY-OWN-SN', // same SN
        ];

        $response = $this->actingAs($this->allUser)
            ->put("/ticketing/assets/{$asset->id}/update", $payload);

        $response->assertSessionDoesntHaveErrors(['serial_number']);
    }

    /**
     * C5: Update → serial_number milik asset lain → error.
     */
    public function test_update_serial_number_duplicate_from_other_asset_fails()
    {
        AssetItem::factory()->create([
            'asset_category_id' => $this->category->id,
            'division_id' => $this->myDivision->id,
            'serial_number' => 'TAKEN-SN',
        ]);

        $asset = AssetItem::factory()->create([
            'asset_category_id' => $this->category->id,
            'division_id' => $this->myDivision->id,
            'serial_number' => 'ORIGINAL-SN',
        ]);

        $payload = [
            'asset_category_id' => $this->category->id,
            'merk' => 'Test',
            'division_id' => $this->myDivision->id,
            'serial_number' => 'TAKEN-SN', // belongs to another asset
        ];

        $response = $this->actingAs($this->allUser)
            ->put("/ticketing/assets/{$asset->id}/update", $payload);

        $response->assertSessionHasErrors(['serial_number']);
    }

    // =========================================================================
    // Grup D: CRUD — Delete (Soft Delete)
    // =========================================================================

    /**
     * D1: Soft delete → deleted_at terisi, record tetap ada di DB.
     */
    public function test_delete_soft_deletes_asset()
    {
        $asset = AssetItem::factory()->create([
            'asset_category_id' => $this->category->id,
            'division_id' => $this->myDivision->id,
        ]);

        $this->actingAs($this->allUser)
            ->delete("/ticketing/assets/{$asset->id}/delete");

        $this->assertSoftDeleted('asset_items', ['id' => $asset->id]);
        // Record masih ada di DB
        $this->assertDatabaseHas('asset_items', ['id' => $asset->id]);
    }

    /**
     * D2: Soft delete → maintenance PENDING dihapus.
     */
    public function test_delete_removes_pending_maintenances()
    {
        $asset = AssetItem::factory()->create([
            'asset_category_id' => $this->category->id,
            'division_id' => $this->myDivision->id,
        ]);
        app(AssetItemService::class)->generateMaintenances($asset);

        // Pastikan ada PENDING sebelum delete
        $this->assertGreaterThan(0, $asset->maintenances()
            ->where('status', MaintenanceStatus::PENDING->value)->count());

        $this->actingAs($this->allUser)
            ->delete("/ticketing/assets/{$asset->id}/delete");

        // PENDING harus sudah dihapus
        $this->assertEquals(0, Maintenance::where('asset_item_id', $asset->id)
            ->where('status', MaintenanceStatus::PENDING->value)->count());
    }

    /**
     * D3: Soft delete → maintenance non-pending tetap utuh.
     */
    public function test_delete_preserves_non_pending_maintenances()
    {
        $asset = AssetItem::factory()->create([
            'asset_category_id' => $this->category->id,
            'division_id' => $this->myDivision->id,
        ]);

        // Buat maintenance non-pending (history)
        Maintenance::create([
            'asset_item_id' => $asset->id,
            'estimation_date' => '2026-01-15',
            'status' => MaintenanceStatus::FINISH->value,
        ]);
        Maintenance::create([
            'asset_item_id' => $asset->id,
            'estimation_date' => '2026-02-15',
            'status' => MaintenanceStatus::CONFIRMED->value,
        ]);
        // Dan satu PENDING
        Maintenance::create([
            'asset_item_id' => $asset->id,
            'estimation_date' => '2026-03-15',
            'status' => MaintenanceStatus::PENDING->value,
        ]);

        $this->actingAs($this->allUser)
            ->delete("/ticketing/assets/{$asset->id}/delete");

        // Non-pending tetap ada
        $this->assertEquals(2, Maintenance::where('asset_item_id', $asset->id)
            ->whereIn('status', [
                MaintenanceStatus::FINISH->value,
                MaintenanceStatus::CONFIRMED->value,
            ])->count());
        // PENDING sudah dihapus
        $this->assertEquals(0, Maintenance::where('asset_item_id', $asset->id)
            ->where('status', MaintenanceStatus::PENDING->value)->count());
    }

    /**
     * D4: Soft deleted item tidak tampil di datatable.
     */
    public function test_soft_deleted_asset_excluded_from_datatable()
    {
        $asset = AssetItem::factory()->create([
            'asset_category_id' => $this->category->id,
            'division_id' => $this->myDivision->id,
            'merk' => 'Deleted Asset',
        ]);

        $this->actingAs($this->allUser)
            ->delete("/ticketing/assets/{$asset->id}/delete");

        $response = $this->actingAs($this->allUser)
            ->get('/ticketing/assets/datatable');

        $response->assertOk();
        $response->assertJsonPath('total', 0);
    }

    // =========================================================================
    // Grup E: Datatable — Search & Filter
    // =========================================================================

    /**
     * E1: Global search by merk.
     */
    public function test_datatable_global_search_by_merk()
    {
        AssetItem::factory()->create([
            'asset_category_id' => $this->category->id,
            'division_id' => $this->myDivision->id,
            'merk' => 'Dell',
        ]);
        // Noise
        AssetItem::factory()->create([
            'asset_category_id' => $this->category->id,
            'division_id' => $this->myDivision->id,
            'merk' => 'HP',
        ]);

        $response = $this->actingAs($this->allUser)
            ->get('/ticketing/assets/datatable?search=Dell');

        $response->assertOk();
        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('data.0.merk', 'Dell');
    }

    /**
     * E2: Global search by model.
     */
    public function test_datatable_global_search_by_model()
    {
        AssetItem::factory()->create([
            'asset_category_id' => $this->category->id,
            'division_id' => $this->myDivision->id,
            'model' => 'XPS 15',
        ]);
        // Noise
        AssetItem::factory()->create([
            'asset_category_id' => $this->category->id,
            'division_id' => $this->myDivision->id,
            'model' => 'ThinkPad',
        ]);

        $response = $this->actingAs($this->allUser)
            ->get('/ticketing/assets/datatable?search=XPS');

        $response->assertOk();
        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('data.0.model', 'XPS 15');
    }

    /**
     * E3: Global search by serial_number.
     */
    public function test_datatable_global_search_by_serial_number()
    {
        AssetItem::factory()->create([
            'asset_category_id' => $this->category->id,
            'division_id' => $this->myDivision->id,
            'serial_number' => 'SN-ABC-123',
        ]);
        // Noise
        AssetItem::factory()->create([
            'asset_category_id' => $this->category->id,
            'division_id' => $this->myDivision->id,
            'serial_number' => 'SN-XYZ-999',
        ]);

        $response = $this->actingAs($this->allUser)
            ->get('/ticketing/assets/datatable?search=ABC-123');

        $response->assertOk();
        $response->assertJsonPath('total', 1);
    }

    /**
     * E4: Global search by category name.
     */
    public function test_datatable_global_search_by_category_name()
    {
        $laptopCat = AssetCategory::factory()->create([
            'name' => 'Laptop Kantor',
            'division_id' => $this->myDivision->id,
        ]);
        $printerCat = AssetCategory::factory()->create([
            'name' => 'Printer',
            'division_id' => $this->myDivision->id,
        ]);

        AssetItem::factory()->create([
            'asset_category_id' => $laptopCat->id,
            'division_id' => $this->myDivision->id,
        ]);
        // Noise
        AssetItem::factory()->create([
            'asset_category_id' => $printerCat->id,
            'division_id' => $this->myDivision->id,
        ]);

        $response = $this->actingAs($this->allUser)
            ->get('/ticketing/assets/datatable?search=Laptop Kantor');

        $response->assertOk();
        $response->assertJsonPath('total', 1);
    }

    /**
     * E5: Global search by division name.
     */
    public function test_datatable_global_search_by_division_name()
    {
        $divIT = Division::factory()->create(['name' => 'IT Department']);
        AssetItem::factory()->create([
            'asset_category_id' => $this->category->id,
            'division_id' => $divIT->id,
        ]);
        // Noise
        AssetItem::factory()->create([
            'asset_category_id' => $this->category->id,
            'division_id' => $this->myDivision->id,
        ]);

        $response = $this->actingAs($this->allUser)
            ->get('/ticketing/assets/datatable?search=IT Department');

        $response->assertOk();
        $response->assertJsonPath('total', 1);
    }

    /**
     * E6: Global search by user name.
     */
    public function test_datatable_global_search_by_user_name()
    {
        $budi = User::factory()->create(['name' => 'Budi Santoso', 'division_id' => $this->myDivision->id]);
        $asset = AssetItem::factory()->create([
            'asset_category_id' => $this->category->id,
            'division_id' => $this->myDivision->id,
        ]);
        $asset->users()->attach($budi->id);

        // Noise: asset tanpa user Budi
        AssetItem::factory()->create([
            'asset_category_id' => $this->category->id,
            'division_id' => $this->myDivision->id,
        ]);

        $response = $this->actingAs($this->allUser)
            ->get('/ticketing/assets/datatable?search=Budi');

        $response->assertOk();
        $response->assertJsonPath('total', 1);
    }

    /**
     * E7: Individual filter merk.
     */
    public function test_datatable_individual_filter_merk()
    {
        AssetItem::factory()->create([
            'asset_category_id' => $this->category->id,
            'division_id' => $this->myDivision->id,
            'merk' => 'Asus',
        ]);
        // Noise
        AssetItem::factory()->create([
            'asset_category_id' => $this->category->id,
            'division_id' => $this->myDivision->id,
            'merk' => 'Acer',
        ]);

        $response = $this->actingAs($this->allUser)
            ->get('/ticketing/assets/datatable?merk=Asus');

        $response->assertOk();
        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('data.0.merk', 'Asus');
    }

    /**
     * E8: Individual filter model.
     */
    public function test_datatable_individual_filter_model()
    {
        AssetItem::factory()->create([
            'asset_category_id' => $this->category->id,
            'division_id' => $this->myDivision->id,
            'model' => 'EliteBook 840',
        ]);
        // Noise
        AssetItem::factory()->create([
            'asset_category_id' => $this->category->id,
            'division_id' => $this->myDivision->id,
            'model' => 'ProBook 450',
        ]);

        $response = $this->actingAs($this->allUser)
            ->get('/ticketing/assets/datatable?model=EliteBook');

        $response->assertOk();
        $response->assertJsonPath('total', 1);
    }

    /**
     * E9: Individual filter serial_number.
     */
    public function test_datatable_individual_filter_serial_number()
    {
        AssetItem::factory()->create([
            'asset_category_id' => $this->category->id,
            'division_id' => $this->myDivision->id,
            'serial_number' => 'FILTER-SN-001',
        ]);
        // Noise
        AssetItem::factory()->create([
            'asset_category_id' => $this->category->id,
            'division_id' => $this->myDivision->id,
            'serial_number' => 'FILTER-SN-999',
        ]);

        $response = $this->actingAs($this->allUser)
            ->get('/ticketing/assets/datatable?serial_number=SN-001');

        $response->assertOk();
        $response->assertJsonPath('total', 1);
    }

    /**
     * E10: Global search no match → total = 0.
     */
    public function test_datatable_search_no_match()
    {
        AssetItem::factory()->create([
            'asset_category_id' => $this->category->id,
            'division_id' => $this->myDivision->id,
        ]);

        $response = $this->actingAs($this->allUser)
            ->get('/ticketing/assets/datatable?search=TidakAdaDataIni');

        $response->assertOk();
        $response->assertJsonPath('total', 0);
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
            AssetItem::factory()->create([
                'asset_category_id' => $this->category->id,
                'division_id' => $this->myDivision->id,
            ]);
        }

        $response = $this->actingAs($this->allUser)
            ->get('/ticketing/assets/datatable?limit=2');

        $response->assertOk();
        $response->assertJsonPath('total', 5);
        $response->assertJsonPath('per_page', 2);
        $this->assertCount(2, $response->json('data'));
        $response->assertJsonPath('last_page', 3);
    }

    /**
     * F2: Pagination — halaman kedua menampilkan data yang benar.
     */
    public function test_datatable_pagination()
    {
        $merks = ['Alpha', 'Bravo', 'Charlie', 'Delta', 'Echo'];
        foreach ($merks as $merk) {
            AssetItem::factory()->create([
                'asset_category_id' => $this->category->id,
                'division_id' => $this->myDivision->id,
                'merk' => $merk,
            ]);
        }

        // sort_by=merk, asc → Alpha, Bravo, Charlie, Delta, Echo
        // Page 1, limit 2
        $response = $this->actingAs($this->allUser)
            ->get('/ticketing/assets/datatable?sort_by=merk&sort_direction=asc&limit=2&page=1');

        $response->assertOk();
        $response->assertJsonPath('current_page', 1);
        $response->assertJsonPath('data.0.merk', 'Alpha');
        $response->assertJsonPath('data.1.merk', 'Bravo');

        // Page 2
        $response = $this->actingAs($this->allUser)
            ->get('/ticketing/assets/datatable?sort_by=merk&sort_direction=asc&limit=2&page=2');

        $response->assertOk();
        $response->assertJsonPath('data.0.merk', 'Charlie');
        $response->assertJsonPath('data.1.merk', 'Delta');

        // Page 3 (sisa 1)
        $response = $this->actingAs($this->allUser)
            ->get('/ticketing/assets/datatable?sort_by=merk&sort_direction=asc&limit=2&page=3');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $response->assertJsonPath('data.0.merk', 'Echo');
    }

    /**
     * F3: Sort by merk ASC dan DESC.
     */
    public function test_datatable_sort_by_merk()
    {
        AssetItem::factory()->create([
            'asset_category_id' => $this->category->id,
            'division_id' => $this->myDivision->id,
            'merk' => 'Zebra',
        ]);
        AssetItem::factory()->create([
            'asset_category_id' => $this->category->id,
            'division_id' => $this->myDivision->id,
            'merk' => 'Apple',
        ]);

        $response = $this->actingAs($this->allUser)
            ->get('/ticketing/assets/datatable?sort_by=merk&sort_direction=asc');
        $response->assertOk();
        $response->assertJsonPath('data.0.merk', 'Apple');
        $response->assertJsonPath('data.1.merk', 'Zebra');

        $response = $this->actingAs($this->allUser)
            ->get('/ticketing/assets/datatable?sort_by=merk&sort_direction=desc');
        $response->assertOk();
        $response->assertJsonPath('data.0.merk', 'Zebra');
        $response->assertJsonPath('data.1.merk', 'Apple');
    }

    /**
     * F4: Default sort = created_at DESC (terbaru dulu).
     */
    public function test_datatable_default_sort_is_created_at_desc()
    {
        $first = AssetItem::factory()->create([
            'asset_category_id' => $this->category->id,
            'division_id' => $this->myDivision->id,
            'merk' => 'First',
            'created_at' => now()->subDay(),
        ]);
        $second = AssetItem::factory()->create([
            'asset_category_id' => $this->category->id,
            'division_id' => $this->myDivision->id,
            'merk' => 'Second',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->allUser)
            ->get('/ticketing/assets/datatable');

        $response->assertOk();
        // Terbaru (Second) harus pertama
        $response->assertJsonPath('data.0.merk', 'Second');
        $response->assertJsonPath('data.1.merk', 'First');
    }

    /**
     * F5: Default limit = 10.
     */
    public function test_datatable_default_limit_is_10()
    {
        for ($i = 1; $i <= 12; $i++) {
            AssetItem::factory()->create([
                'asset_category_id' => $this->category->id,
                'division_id' => $this->myDivision->id,
            ]);
        }

        $response = $this->actingAs($this->allUser)
            ->get('/ticketing/assets/datatable');

        $response->assertOk();
        $response->assertJsonPath('total', 12);
        $response->assertJsonPath('per_page', 10);
        $this->assertCount(10, $response->json('data'));
    }

    /**
     * F6: Excel export → XLSX.
     */
    public function test_print_excel_returns_xlsx()
    {
        AssetItem::factory()->create([
            'asset_category_id' => $this->category->id,
            'division_id' => $this->myDivision->id,
        ]);

        $response = $this->actingAs($this->allUser)
            ->get('/ticketing/assets/print/excel');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    // =========================================================================
    // Grup G: Request Validation
    // =========================================================================

    /**
     * G1: Required fields kosong → error.
     */
    public function test_store_validation_requires_mandatory_fields()
    {
        $response = $this->actingAs($this->allUser)
            ->post('/ticketing/assets/store', []);

        $response->assertSessionHasErrors(['asset_category_id', 'division_id']);
    }

    /**
     * G2: asset_category_id harus ada di DB.
     */
    public function test_store_validation_category_must_exist()
    {
        $payload = [
            'asset_category_id' => 99999,
            'division_id' => $this->myDivision->id,
        ];

        $response = $this->actingAs($this->allUser)
            ->post('/ticketing/assets/store', $payload);

        $response->assertSessionHasErrors(['asset_category_id']);
    }

    /**
     * G3: division_id harus ada di DB.
     */
    public function test_store_validation_division_must_exist()
    {
        $payload = [
            'asset_category_id' => $this->category->id,
            'division_id' => 99999,
        ];

        $response = $this->actingAs($this->allUser)
            ->post('/ticketing/assets/store', $payload);

        $response->assertSessionHasErrors(['division_id']);
    }

    /**
     * G4: user_ids.* harus ada di tabel users.
     */
    public function test_store_validation_user_ids_must_exist()
    {
        $payload = [
            'asset_category_id' => $this->category->id,
            'division_id' => $this->myDivision->id,
            'user_ids' => [99999],
        ];

        $response = $this->actingAs($this->allUser)
            ->post('/ticketing/assets/store', $payload);

        $response->assertSessionHasErrors(['user_ids.0']);
    }

    // =========================================================================
    // Grup H: Security & Ownership Enforcement (Data Protection)
    // =========================================================================

    /**
     * H1: User Personal → Store dipaksa ke division_id & user_id sendiri.
     * Meskipun request mengirim division_id & user_ids lain, tetap disimpan milik user login.
     */
    public function test_personal_store_forces_own_division_and_user()
    {
        $otherUser = User::factory()->create(['division_id' => $this->otherDivision->id]);

        $payload = [
            'asset_category_id' => $this->category->id,
            'merk' => 'Injected Asset',
            'division_id' => $this->otherDivision->id, // coba inject divisi lain
            'user_ids' => [$otherUser->id],             // coba inject user lain
        ];

        $this->actingAs($this->personalUser)
            ->post('/ticketing/assets/store', $payload);

        $asset = AssetItem::where('merk', 'Injected Asset')->first();
        $this->assertNotNull($asset);

        // division_id harus dipaksa ke divisi user login
        $this->assertEquals($this->myDivision->id, $asset->division_id);

        // user_ids harus dipaksa hanya ke user login sendiri
        $this->assertEquals(1, $asset->users()->count());
        $this->assertTrue($asset->users->contains($this->personalUser));
        $this->assertFalse($asset->users->contains($otherUser));
    }

    /**
     * H2: User Divisi → Store dipaksa ke division_id sendiri.
     */
    public function test_division_store_forces_own_division()
    {
        $payload = [
            'asset_category_id' => $this->category->id,
            'merk' => 'Div Injected',
            'division_id' => $this->otherDivision->id, // coba inject divisi lain
        ];

        $this->actingAs($this->divisionUser)
            ->post('/ticketing/assets/store', $payload);

        $asset = AssetItem::where('merk', 'Div Injected')->first();
        $this->assertNotNull($asset);

        // division_id harus dipaksa ke divisi user login
        $this->assertEquals($this->myDivision->id, $asset->division_id);
    }

    /**
     * H3: User Divisi → Store memfilter user_ids yang beda divisi.
     * Hanya simpan user yang satu divisi dengan user login.
     */
    public function test_division_store_filters_cross_division_users()
    {
        $sameDivUser = User::factory()->create(['division_id' => $this->myDivision->id]);
        $otherDivUser = User::factory()->create(['division_id' => $this->otherDivision->id]);

        $payload = [
            'asset_category_id' => $this->category->id,
            'merk' => 'Filtered Asset',
            'division_id' => $this->myDivision->id,
            'user_ids' => [$sameDivUser->id, $otherDivUser->id], // campur 2 divisi
        ];

        $this->actingAs($this->divisionUser)
            ->post('/ticketing/assets/store', $payload);

        $asset = AssetItem::where('merk', 'Filtered Asset')->first();
        $this->assertNotNull($asset);

        // Hanya user satu divisi yang tersimpan
        $this->assertEquals(1, $asset->users()->count());
        $this->assertTrue($asset->users->contains($sameDivUser));
        $this->assertFalse($asset->users->contains($otherDivUser));
    }

    /**
     * H4: User "All" → Store memfilter user_ids yang beda dengan division_id pilihan.
     * Bebas pilih division, tapi user_ids harus satu divisi dengan division_id yang dipilih.
     */
    public function test_all_store_filters_users_to_chosen_division()
    {
        $userInOther = User::factory()->create(['division_id' => $this->otherDivision->id]);
        $userInMy = User::factory()->create(['division_id' => $this->myDivision->id]);

        // allUser pilih otherDivision, tapi kirim user dari myDivision juga
        $payload = [
            'asset_category_id' => $this->category->id,
            'merk' => 'All Level Asset',
            'division_id' => $this->otherDivision->id,
            'user_ids' => [$userInOther->id, $userInMy->id], // campur
        ];

        $this->actingAs($this->allUser)
            ->post('/ticketing/assets/store', $payload);

        $asset = AssetItem::where('merk', 'All Level Asset')->first();
        $this->assertNotNull($asset);

        // division_id sesuai pilihan (otherDivision)
        $this->assertEquals($this->otherDivision->id, $asset->division_id);

        // Hanya user yang satu divisi dengan pilihan yang tersimpan
        $this->assertEquals(1, $asset->users()->count());
        $this->assertTrue($asset->users->contains($userInOther));
        $this->assertFalse($asset->users->contains($userInMy));
    }

    /**
     * H5: User Personal → Update dipaksa ke division_id & user_id sendiri.
     */
    public function test_personal_update_forces_own_division_and_user()
    {
        $asset = AssetItem::factory()->create([
            'asset_category_id' => $this->category->id,
            'division_id' => $this->myDivision->id,
        ]);
        $asset->users()->attach($this->personalUser->id);

        $otherUser = User::factory()->create(['division_id' => $this->otherDivision->id]);

        $payload = [
            'asset_category_id' => $this->category->id,
            'merk' => 'Updated Personal',
            'division_id' => $this->otherDivision->id, // coba inject
            'user_ids' => [$otherUser->id],             // coba inject
        ];

        $this->actingAs($this->personalUser)
            ->put("/ticketing/assets/{$asset->id}/update", $payload);

        $asset->refresh();
        // division_id dipaksa ke divisi sendiri
        $this->assertEquals($this->myDivision->id, $asset->division_id);
        // user_ids dipaksa ke user sendiri
        $this->assertEquals(1, $asset->users()->count());
        $this->assertTrue($asset->users->contains($this->personalUser));
    }

    /**
     * H6: User Divisi → Update dipaksa ke division_id sendiri & filter user yang sesuai.
     */
    public function test_division_update_forces_own_division_and_filters_users()
    {
        $asset = AssetItem::factory()->create([
            'asset_category_id' => $this->category->id,
            'division_id' => $this->myDivision->id,
        ]);

        $sameDivUser = User::factory()->create(['division_id' => $this->myDivision->id]);
        $otherDivUser = User::factory()->create(['division_id' => $this->otherDivision->id]);

        $payload = [
            'asset_category_id' => $this->category->id,
            'merk' => 'Updated Division',
            'division_id' => $this->otherDivision->id, // coba inject divisi lain
            'user_ids' => [$sameDivUser->id, $otherDivUser->id],
        ];

        $this->actingAs($this->divisionUser)
            ->put("/ticketing/assets/{$asset->id}/update", $payload);

        $asset->refresh();
        // division_id dipaksa ke divisi sendiri
        $this->assertEquals($this->myDivision->id, $asset->division_id);
        // Hanya user satu divisi yang tersimpan
        $this->assertEquals(1, $asset->users()->count());
        $this->assertTrue($asset->users->contains($sameDivUser));
        $this->assertFalse($asset->users->contains($otherDivUser));
    }

    /**
     * H7: User "All" → Update memfilter user_ids agar sesuai division_id yang dipilih.
     */
    public function test_all_update_filters_users_to_chosen_division()
    {
        $asset = AssetItem::factory()->create([
            'asset_category_id' => $this->category->id,
            'division_id' => $this->myDivision->id,
        ]);

        $userInOther = User::factory()->create(['division_id' => $this->otherDivision->id]);
        $userInMy = User::factory()->create(['division_id' => $this->myDivision->id]);

        // Update: pindah ke otherDivision, campur user 2 divisi
        $payload = [
            'asset_category_id' => $this->category->id,
            'merk' => 'Updated All',
            'division_id' => $this->otherDivision->id,
            'user_ids' => [$userInOther->id, $userInMy->id],
        ];

        $this->actingAs($this->allUser)
            ->put("/ticketing/assets/{$asset->id}/update", $payload);

        $asset->refresh();
        // division_id sesuai pilihan (otherDivision)
        $this->assertEquals($this->otherDivision->id, $asset->division_id);
        // Hanya user yang satu divisi dengan pilihan yang tersimpan
        $this->assertEquals(1, $asset->users()->count());
        $this->assertTrue($asset->users->contains($userInOther));
        $this->assertFalse($asset->users->contains($userInMy));
    }
}
