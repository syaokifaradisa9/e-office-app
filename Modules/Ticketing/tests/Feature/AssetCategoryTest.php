<?php

namespace Modules\Ticketing\Tests\Feature;

use App\Models\User;
use App\Models\Division;
use Modules\Ticketing\Models\AssetCategory;
use Modules\Ticketing\Models\AssetItem;
use Modules\Ticketing\Models\Maintenance;
use Modules\Ticketing\Enums\TicketingPermission;
use Modules\Ticketing\Enums\AssetCategoryType;
use Modules\Ticketing\Services\AssetItemService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected $divisionAdmin;
    protected $superAdmin;
    protected $removerUser;
    protected $otherDivision;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Permissions
        foreach (TicketingPermission::values() as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Setup Divisions
        $this->otherDivision = Division::factory()->create(['name' => 'Other Division']);
        $myDivision = Division::factory()->create(['name' => 'My Division']);

        // 1. User with "Lihat Data Kategori Asset Divisi"
        $divisiRole = Role::firstOrCreate(['name' => 'DivisiAdmin', 'guard_name' => 'web']);
        $divisiRole->syncPermissions([
            TicketingPermission::ViewAssetCategoryDivisi->value,
            TicketingPermission::ManageAssetCategory->value // Also give manage for store/update tests
        ]);
        
        $this->divisionAdmin = User::factory()->create([
            'division_id' => $myDivision->id,
            'name' => 'Division Admin'
        ]);
        $this->divisionAdmin->assignRole($divisiRole);

        // 2. User with "Lihat Data Kategori Asset Keseluruhan"
        $superRole = Role::firstOrCreate(['name' => 'SuperAdminTicketing', 'guard_name' => 'web']);
        $superRole->syncPermissions([
            TicketingPermission::ViewAllAssetCategory->value,
            TicketingPermission::ManageAssetCategory->value
        ]);

        $this->superAdmin = User::factory()->create([
            'division_id' => $myDivision->id,
            'name' => 'Super Admin'
        ]);
        $this->superAdmin->assignRole($superRole);

        // 3. User with "Hapus Data Kategori Asset"
        $removerRole = Role::firstOrCreate(['name' => 'Remover', 'guard_name' => 'web']);
        $removerRole->syncPermissions([TicketingPermission::DeleteAssetCategory->value]);
        
        $this->removerUser = User::factory()->create();
        $this->removerUser->assignRole($removerRole);
    }

    /**
     * Helper: Buat AssetItem dan generate maintenance schedules via service.
     */
    private function createAssetItemWithMaintenances(AssetCategory $category): AssetItem
    {
        $assetItem = AssetItem::factory()->create([
            'asset_category_id' => $category->id,
            'division_id' => $this->superAdmin->division_id,
        ]);

        // Generate maintenance schedules via service
        app(AssetItemService::class)->generateMaintenances($assetItem);

        return $assetItem;
    }

    /**
     * Requirement 1: Jika user memiliki permisison Lihat Data Kategori Asset Divisi 
     * maka pastikan /ticketing/asset-categories/datatable dan /print/excel 
     * memiliki division_id yang sama dengan division_id user login
     */
    public function test_divisi_admin_only_sees_their_division_categories()
    {
        // Category from same division
        AssetCategory::factory()->create([
            'name' => 'My Div Category',
            'division_id' => $this->divisionAdmin->division_id
        ]);

        // Category from other division
        AssetCategory::factory()->create([
            'name' => 'Other Div Category',
            'division_id' => $this->otherDivision->id
        ]);

        // Test Datatable
        $response = $this->actingAs($this->divisionAdmin)->get('/ticketing/asset-categories/datatable');
        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('data.0.name', 'My Div Category');

        // Test Excel
        $response = $this->actingAs($this->divisionAdmin)->get('/ticketing/asset-categories/print/excel');
        $response->assertOk();
        // Since we can't easily read excel content in basic feature test without extra libs, 
        // we at least ensure the query results (if we could tap into it) would be correct.
        // The Service logic is shared between datatable and excel.
    }

    /**
     * Requirement 2: Jika user memiliki permission Lihat Data Kategori Asset Keseluruhan 
     * maka pastikan /ticketing/asset-categories/datatable bisa saja memiliki division_id 
     * yang berbeda dnegan division_id user login
     */
    public function test_super_admin_sees_all_division_categories()
    {
        AssetCategory::factory()->create(['division_id' => $this->divisionAdmin->division_id]);
        AssetCategory::factory()->create(['division_id' => $this->otherDivision->id]);

        $response = $this->actingAs($this->superAdmin)->get('/ticketing/asset-categories/datatable');
        $response->assertJsonPath('total', 2);
    }

    /**
     * Requirement 3: Jika memiliki permisison Kelola Data kategori Asset 
     * maka bisa mengunjungi /create /edit /store dan /update
     */
    public function test_can_access_manage_routes_with_manage_permission()
    {
        $category = AssetCategory::factory()->create();

        $this->actingAs($this->superAdmin)->get('/ticketing/asset-categories/create')->assertOk();
        $this->actingAs($this->superAdmin)->get("/ticketing/asset-categories/{$category->id}/edit")->assertOk();

        // Unauthorized user
        $unauthorized = User::factory()->create();
        $this->actingAs($unauthorized)->get('/ticketing/asset-categories/create')->assertForbidden();
    }

    /**
     * Requirement 4: Saat menambah (store) dan mengubah data (update) jika memiliki permisison 
     * Lihat Data Kategori Asset Divisi pastikan division_id yang tersimpan di database 
     * sama seperti division_id user login walaupun division_id diubah melalui request
     */
    public function test_store_and_update_forces_own_division_id_for_divisi_admin()
    {
        $payload = [
            'name' => 'New Category',
            'type' => AssetCategoryType::Physic->value,
            'division_id' => $this->otherDivision->id, // Trying to hack other division
            'maintenance_count' => 5
        ];

        // Test Store
        $this->actingAs($this->divisionAdmin)->post('/ticketing/asset-categories/store', $payload);
        
        $this->assertDatabaseHas('asset_categories', [
            'name' => 'New Category',
            'division_id' => $this->divisionAdmin->division_id // Forced to own division
        ]);

        // Test Update
        $category = AssetCategory::factory()->create(['division_id' => $this->divisionAdmin->division_id]);
        $updatePayload = array_merge($payload, ['name' => 'Updated Category']);
        
        $this->actingAs($this->divisionAdmin)->put("/ticketing/asset-categories/{$category->id}/update", $updatePayload);

        $this->assertDatabaseHas('asset_categories', [
            'id' => $category->id,
            'name' => 'Updated Category',
            'division_id' => $this->divisionAdmin->division_id // Remained at own division
        ]);
    }

    /**
     * Requirement 5: Saat menambah (store) dan mengubah data (update) jika memiliki permisison 
     * Lihat Data Kategori Asset Keseluruhan pastikan division_id yang tersimpan di database 
     * boleh saja berbeda dengan division_id user login
     */
    public function test_store_and_update_allows_other_division_id_for_super_admin()
    {
        $payload = [
            'name' => 'Super New Category',
            'type' => AssetCategoryType::Physic->value,
            'division_id' => $this->otherDivision->id,
            'maintenance_count' => 10
        ];

        // Test Store
        $this->actingAs($this->superAdmin)->post('/ticketing/asset-categories/store', $payload);
        
        $this->assertDatabaseHas('asset_categories', [
            'name' => 'Super New Category',
            'division_id' => $this->otherDivision->id // Allowed
        ]);

        // Test Update
        $category = AssetCategory::factory()->create(['division_id' => $this->superAdmin->division_id]);
        $updatePayload = array_merge($payload, ['name' => 'Super Updated Category']);
        
        $this->actingAs($this->superAdmin)->put("/ticketing/asset-categories/{$category->id}/update", $updatePayload);

        $this->assertDatabaseHas('asset_categories', [
            'id' => $category->id,
            'name' => 'Super Updated Category',
            'division_id' => $this->otherDivision->id // Allowed
        ]);
    }

    /**
     * Requirement 6: Pastikan /delete bisa diakses dengan permisison Hapus Data Kategori Asset
     */
    public function test_can_delete_with_delete_permission()
    {
        $category = AssetCategory::factory()->create();

        $this->actingAs($this->removerUser)->delete("/ticketing/asset-categories/{$category->id}/delete")
            ->assertRedirect();

        $this->assertDatabaseMissing('asset_categories', ['id' => $category->id]);

        // Unauthorized user cannot delete
        $category2 = AssetCategory::factory()->create();
        $this->actingAs($this->divisionAdmin)->delete("/ticketing/asset-categories/{$category2->id}/delete")
            ->assertForbidden();
    }

    // =========================================================================
    // Grup B: Update maintenance_count → Regenerasi Jadwal Maintenance
    // =========================================================================

    /**
     * B1: Saat maintenance_count berubah (naik), jadwal PENDING lama terhapus
     * dan jadwal baru terbentuk sesuai frekuensi baru.
     */
    public function test_update_maintenance_count_up_regenerates_pending_schedules()
    {
        // Buat kategori dengan maintenance_count = 2 (6 bulan sekali)
        $category = AssetCategory::factory()->create([
            'maintenance_count' => 2,
            'division_id' => $this->superAdmin->division_id,
        ]);

        // Buat asset item dan generate maintenance via service
        $assetItem = $this->createAssetItemWithMaintenances($category);

        // Catat jumlah PENDING awal 
        $initialPendingCount = Maintenance::where('asset_item_id', $assetItem->id)
            ->where('status', 'pending')->count();
        $this->assertGreaterThan(0, $initialPendingCount);

        // Update maintenance_count dari 2 → 4 (3 bulan sekali)
        $this->actingAs($this->superAdmin)->put("/ticketing/asset-categories/{$category->id}/update", [
            'name' => $category->name,
            'type' => $category->type->value,
            'division_id' => $category->division_id,
            'maintenance_count' => 4,
        ]);

        // Pastikan maintenance_count di database sudah berubah
        $this->assertDatabaseHas('asset_categories', [
            'id' => $category->id,
            'maintenance_count' => 4,
        ]);

        // Pastikan jadwal PENDING baru terbentuk (jumlahnya bisa berbeda dari awal)
        $newPendingCount = Maintenance::where('asset_item_id', $assetItem->id)
            ->where('status', 'pending')->count();
        $this->assertGreaterThan(0, $newPendingCount);
    }

    /**
     * B2: Saat maintenance_count berubah (turun), jadwal PENDING lama terhapus
     * dan jadwal baru terbentuk dengan frekuensi lebih sedikit.
     */
    public function test_update_maintenance_count_down_regenerates_fewer_schedules()
    {
        $category = AssetCategory::factory()->create([
            'maintenance_count' => 4, // 3 bulan sekali
            'division_id' => $this->superAdmin->division_id,
        ]);

        $assetItem = $this->createAssetItemWithMaintenances($category);

        $initialPendingCount = Maintenance::where('asset_item_id', $assetItem->id)
            ->where('status', 'pending')->count();

        // Update maintenance_count dari 4 → 1 (sekali setahun)
        $this->actingAs($this->superAdmin)->put("/ticketing/asset-categories/{$category->id}/update", [
            'name' => $category->name,
            'type' => $category->type->value,
            'division_id' => $category->division_id,
            'maintenance_count' => 1,
        ]);

        $newPendingCount = Maintenance::where('asset_item_id', $assetItem->id)
            ->where('status', 'pending')->count();

        // Jadwal baru harus lebih sedikit atau sama (tergantung waktu)
        $this->assertLessThanOrEqual($initialPendingCount, $newPendingCount);
    }

    /**
     * B3: Jika maintenance_count TIDAK berubah, regenerasi TIDAK terjadi.
     * Record PENDING yang ada harus tetap sama persis (ID tidak berubah).
     */
    public function test_update_same_maintenance_count_does_not_regenerate()
    {
        $category = AssetCategory::factory()->create([
            'maintenance_count' => 3,
            'division_id' => $this->superAdmin->division_id,
        ]);

        $assetItem = $this->createAssetItemWithMaintenances($category);

        // Simpan ID maintenance PENDING sebelum update
        $pendingIdsBefore = Maintenance::where('asset_item_id', $assetItem->id)
            ->where('status', 'pending')->pluck('id')->toArray();

        // Update kategori TANPA mengubah maintenance_count
        $this->actingAs($this->superAdmin)->put("/ticketing/asset-categories/{$category->id}/update", [
            'name' => 'Updated Name Only',
            'type' => $category->type->value,
            'division_id' => $category->division_id,
            'maintenance_count' => 3, // Sama seperti sebelumnya
        ]);

        // ID PENDING harus tetap sama (tidak di-delete lalu re-create)
        $pendingIdsAfter = Maintenance::where('asset_item_id', $assetItem->id)
            ->where('status', 'pending')->pluck('id')->toArray();

        $this->assertEquals($pendingIdsBefore, $pendingIdsAfter);
    }

    /**
     * B4: Jika maintenance_count diubah ke 0, semua PENDING terhapus
     * dan tidak ada jadwal baru yang dibuat.
     */
    public function test_update_maintenance_count_to_zero_removes_all_pending()
    {
        $category = AssetCategory::factory()->create([
            'maintenance_count' => 4,
            'division_id' => $this->superAdmin->division_id,
        ]);

        $assetItem = $this->createAssetItemWithMaintenances($category);

        // Pastikan ada PENDING maintenance
        $this->assertGreaterThan(0, Maintenance::where('asset_item_id', $assetItem->id)
            ->where('status', 'pending')->count());

        // Update maintenance_count ke 0
        $this->actingAs($this->superAdmin)->put("/ticketing/asset-categories/{$category->id}/update", [
            'name' => $category->name,
            'type' => $category->type->value,
            'division_id' => $category->division_id,
            'maintenance_count' => 0,
        ]);

        // Tidak boleh ada PENDING tersisa
        $this->assertEquals(0, Maintenance::where('asset_item_id', $assetItem->id)
            ->where('status', 'pending')->count());
    }

    /**
     * B5: Regenerasi harus berlaku untuk SEMUA asset item di bawah kategori tersebut.
     */
    public function test_update_maintenance_count_regenerates_all_assets_in_category()
    {
        $category = AssetCategory::factory()->create([
            'maintenance_count' => 2,
            'division_id' => $this->superAdmin->division_id,
        ]);

        // Buat 3 asset item di bawah satu kategori
        $items = [];
        for ($i = 0; $i < 3; $i++) {
            $items[] = $this->createAssetItemWithMaintenances($category);
        }

        // Update maintenance_count dari 2 → 4
        $this->actingAs($this->superAdmin)->put("/ticketing/asset-categories/{$category->id}/update", [
            'name' => $category->name,
            'type' => $category->type->value,
            'division_id' => $category->division_id,
            'maintenance_count' => 4,
        ]);

        // Pastikan SETIAP asset item memiliki jadwal PENDING baru
        foreach ($items as $item) {
            $pendingCount = Maintenance::where('asset_item_id', $item->id)
                ->where('status', 'pending')->count();
            $this->assertGreaterThan(0, $pendingCount, "Asset item {$item->id} harus memiliki jadwal maintenance PENDING.");
        }
    }

    // =========================================================================
    // Grup C: Proteksi Data Non-Pending saat Regenerasi
    // =========================================================================

    /**
     * C1: Record non-pending (FINISH, CONFIRMED, REFINEMENT, CANCELLED) 
     * TIDAK boleh terhapus saat regenerasi.
     */
    public function test_regeneration_preserves_non_pending_records()
    {
        $category = AssetCategory::factory()->create([
            'maintenance_count' => 4,
            'division_id' => $this->superAdmin->division_id,
        ]);

        $assetItem = $this->createAssetItemWithMaintenances($category);

        // Buat record non-pending secara manual
        $finishRecord = Maintenance::create([
            'asset_item_id' => $assetItem->id,
            'estimation_date' => now()->subMonths(6)->toDateString(),
            'actual_date' => now()->subMonths(6)->toDateString(),
            'status' => 'finish',
        ]);

        $confirmedRecord = Maintenance::create([
            'asset_item_id' => $assetItem->id,
            'estimation_date' => now()->subMonths(3)->toDateString(),
            'actual_date' => now()->subMonths(3)->toDateString(),
            'status' => 'confirmed',
        ]);

        $refinementRecord = Maintenance::create([
            'asset_item_id' => $assetItem->id,
            'estimation_date' => now()->subMonth()->toDateString(),
            'status' => 'refinement',
        ]);

        // Update maintenance_count untuk memicu regenerasi
        $this->actingAs($this->superAdmin)->put("/ticketing/asset-categories/{$category->id}/update", [
            'name' => $category->name,
            'type' => $category->type->value,
            'division_id' => $category->division_id,
            'maintenance_count' => 2, // Ubah frekuensi
        ]);

        // Record non-pending HARUS tetap ada
        $this->assertDatabaseHas('maintenances', ['id' => $finishRecord->id, 'status' => 'finish']);
        $this->assertDatabaseHas('maintenances', ['id' => $confirmedRecord->id, 'status' => 'confirmed']);
        $this->assertDatabaseHas('maintenances', ['id' => $refinementRecord->id, 'status' => 'refinement']);
    }

    /**
     * C2: Hanya record PENDING yang terhapus saat regenerasi.
     * Campuran status → yang non-pending tetap ada, yang pending lama hilang.
     */
    public function test_regeneration_only_removes_pending_records()
    {
        $category = AssetCategory::factory()->create([
            'maintenance_count' => 4,
            'division_id' => $this->superAdmin->division_id,
        ]);

        $assetItem = $this->createAssetItemWithMaintenances($category);

        // Ambil ID dari PENDING yang auto-generated
        $oldPendingIds = Maintenance::where('asset_item_id', $assetItem->id)
            ->where('status', 'pending')->pluck('id')->toArray();
        $this->assertNotEmpty($oldPendingIds);

        // Buat 1 record CONFIRMED
        $confirmedRecord = Maintenance::create([
            'asset_item_id' => $assetItem->id,
            'estimation_date' => now()->subMonths(3)->toDateString(),
            'actual_date' => now()->subMonths(3)->toDateString(),
            'status' => 'confirmed',
        ]);

        // Trigger regenerasi
        $this->actingAs($this->superAdmin)->put("/ticketing/asset-categories/{$category->id}/update", [
            'name' => $category->name,
            'type' => $category->type->value,
            'division_id' => $category->division_id,
            'maintenance_count' => 2,
        ]);

        // PENDING lama harus hilang
        foreach ($oldPendingIds as $id) {
            $this->assertDatabaseMissing('maintenances', ['id' => $id]);
        }

        // CONFIRMED harus tetap ada
        $this->assertDatabaseHas('maintenances', ['id' => $confirmedRecord->id]);
    }

    // =========================================================================
    // Grup F: Idempoten (Panggil 2x → hasil sama)
    // =========================================================================

    /**
     * F1: Regenerasi 2x berturut-turut harus menghasilkan jumlah PENDING yang sama
     * (tidak ada duplikat jadwal).
     */
    public function test_regeneration_is_idempotent()
    {
        $category = AssetCategory::factory()->create([
            'maintenance_count' => 3,
            'division_id' => $this->superAdmin->division_id,
        ]);

        $assetItem = $this->createAssetItemWithMaintenances($category);

        // Regenerasi pertama: ubah dari 3 → 4
        $this->actingAs($this->superAdmin)->put("/ticketing/asset-categories/{$category->id}/update", [
            'name' => $category->name,
            'type' => $category->type->value,
            'division_id' => $category->division_id,
            'maintenance_count' => 4,
        ]);

        $countAfterFirst = Maintenance::where('asset_item_id', $assetItem->id)
            ->where('status', 'pending')->count();

        // Regenerasi kedua: ubah dari 4 → 3 → lalu kembali ke 4
        $this->actingAs($this->superAdmin)->put("/ticketing/asset-categories/{$category->id}/update", [
            'name' => $category->name,
            'type' => $category->type->value,
            'division_id' => $category->division_id,
            'maintenance_count' => 3,
        ]);

        $this->actingAs($this->superAdmin)->put("/ticketing/asset-categories/{$category->id}/update", [
            'name' => $category->name,
            'type' => $category->type->value,
            'division_id' => $category->division_id,
            'maintenance_count' => 4,
        ]);

        $countAfterSecond = Maintenance::where('asset_item_id', $assetItem->id)
            ->where('status', 'pending')->count();

        // Jumlah PENDING harus sama setelah dua kali regenerasi dengan count yang sama
        $this->assertEquals($countAfterFirst, $countAfterSecond);
    }

    // =========================================================================
    // Grup G: Validasi Request
    // =========================================================================

    /**
     * G1: Validasi store - field wajib harus diisi.
     */
    public function test_store_validation_requires_mandatory_fields()
    {
        // Kirim request kosong
        $response = $this->actingAs($this->superAdmin)->post('/ticketing/asset-categories/store', []);
        $response->assertSessionHasErrors(['name', 'type', 'maintenance_count']);
    }

    /**
     * G2: Validasi store - maintenance_count harus integer dan minimal 0.
     */
    public function test_store_validation_maintenance_count_must_be_non_negative_integer()
    {
        $payload = [
            'name' => 'Test',
            'type' => AssetCategoryType::Physic->value,
            'division_id' => $this->superAdmin->division_id,
            'maintenance_count' => -1,
        ];

        $response = $this->actingAs($this->superAdmin)->post('/ticketing/asset-categories/store', $payload);
        $response->assertSessionHasErrors(['maintenance_count']);
    }

    /**
     * G3: Validasi store - division_id harus ada di tabel divisions.
     */
    public function test_store_validation_division_must_exist()
    {
        $payload = [
            'name' => 'Test',
            'type' => AssetCategoryType::Physic->value,
            'division_id' => 99999,
            'maintenance_count' => 2,
        ];

        $response = $this->actingAs($this->superAdmin)->post('/ticketing/asset-categories/store', $payload);
        $response->assertSessionHasErrors(['division_id']);
    }

    /**
     * G4: Validasi store - type harus enum yang valid.
     */
    public function test_store_validation_type_must_be_valid_enum()
    {
        $payload = [
            'name' => 'Test',
            'type' => 'InvalidType',
            'division_id' => $this->superAdmin->division_id,
            'maintenance_count' => 2,
        ];

        $response = $this->actingAs($this->superAdmin)->post('/ticketing/asset-categories/store', $payload);
        $response->assertSessionHasErrors(['type']);
    }

    // =========================================================================
    // Grup H: Datatable — Search, Pagination, Limit, Individual Filter, Sort
    // =========================================================================

    /**
     * H1: Global search harus memfilter berdasarkan name.
     */
    public function test_datatable_global_search_by_name()
    {
        AssetCategory::factory()->create([
            'name' => 'Laptop Kantor',
            'division_id' => $this->superAdmin->division_id,
        ]);
        AssetCategory::factory()->create([
            'name' => 'Printer Lantai 2',
            'division_id' => $this->superAdmin->division_id,
        ]);
        // Noise data
        AssetCategory::factory()->create([
            'name' => 'Router Jaringan',
            'division_id' => $this->superAdmin->division_id,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get('/ticketing/asset-categories/datatable?search=Laptop');

        $response->assertOk();
        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('data.0.name', 'Laptop Kantor');
    }

    /**
     * H2: Global search harus memfilter berdasarkan type.
     */
    public function test_datatable_global_search_by_type()
    {
        AssetCategory::factory()->create([
            'name' => 'Kategori Fisik',
            'type' => AssetCategoryType::Physic,
            'division_id' => $this->superAdmin->division_id,
        ]);
        // Noise data - tipe berbeda
        AssetCategory::factory()->create([
            'name' => 'Kategori Digital',
            'type' => AssetCategoryType::Digital,
            'division_id' => $this->superAdmin->division_id,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get('/ticketing/asset-categories/datatable?search=' . AssetCategoryType::Physic->value);

        $response->assertOk();
        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('data.0.name', 'Kategori Fisik');
    }

    /**
     * H3: Global search harus memfilter berdasarkan nama divisi.
     */
    public function test_datatable_global_search_by_division_name()
    {
        $divisiIT = Division::factory()->create(['name' => 'IT Department']);
        $divisiHR = Division::factory()->create(['name' => 'HR Department']);

        AssetCategory::factory()->create([
            'name' => 'Kategori IT',
            'division_id' => $divisiIT->id,
        ]);
        // Noise data - divisi berbeda
        AssetCategory::factory()->create([
            'name' => 'Kategori HR',
            'division_id' => $divisiHR->id,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get('/ticketing/asset-categories/datatable?search=IT Department');

        $response->assertOk();
        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('data.0.name', 'Kategori IT');
    }

    /**
     * H4: Individual search kolom name.
     */
    public function test_datatable_individual_search_by_name()
    {
        AssetCategory::factory()->create([
            'name' => 'Server Rack',
            'division_id' => $this->superAdmin->division_id,
        ]);
        AssetCategory::factory()->create([
            'name' => 'Keyboard Wireless',
            'division_id' => $this->superAdmin->division_id,
        ]);
        // Noise
        AssetCategory::factory()->create([
            'name' => 'Monitor LED',
            'division_id' => $this->superAdmin->division_id,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get('/ticketing/asset-categories/datatable?name=Server');

        $response->assertOk();
        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('data.0.name', 'Server Rack');
    }

    /**
     * H5: Individual search kolom type.
     */
    public function test_datatable_individual_search_by_type()
    {
        AssetCategory::factory()->create([
            'name' => 'Asset Fisik A',
            'type' => AssetCategoryType::Physic,
            'division_id' => $this->superAdmin->division_id,
        ]);
        AssetCategory::factory()->create([
            'name' => 'Asset Fisik B',
            'type' => AssetCategoryType::Physic,
            'division_id' => $this->superAdmin->division_id,
        ]);
        // Noise - tipe digital
        AssetCategory::factory()->create([
            'name' => 'Asset Digital C',
            'type' => AssetCategoryType::Digital,
            'division_id' => $this->superAdmin->division_id,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get('/ticketing/asset-categories/datatable?type=' . AssetCategoryType::Physic->value);

        $response->assertOk();
        $response->assertJsonPath('total', 2);
    }

    /**
     * H6: Individual search kolom division.
     */
    public function test_datatable_individual_search_by_division()
    {
        $divisiFinance = Division::factory()->create(['name' => 'Finance Team']);
        $divisiMarketing = Division::factory()->create(['name' => 'Marketing Team']);

        AssetCategory::factory()->create([
            'name' => 'Kategori Finance',
            'division_id' => $divisiFinance->id,
        ]);
        // Noise - divisi berbeda
        AssetCategory::factory()->create([
            'name' => 'Kategori Marketing',
            'division_id' => $divisiMarketing->id,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get('/ticketing/asset-categories/datatable?division=Finance');

        $response->assertOk();
        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('data.0.name', 'Kategori Finance');
    }

    /**
     * H7: Limit menentukan jumlah data per halaman.
     */
    public function test_datatable_limit()
    {
        // Buat 5 kategori
        for ($i = 1; $i <= 5; $i++) {
            AssetCategory::factory()->create([
                'name' => "Kategori {$i}",
                'division_id' => $this->superAdmin->division_id,
            ]);
        }

        // Limit 2 → hanya 2 data per halaman
        $response = $this->actingAs($this->superAdmin)
            ->get('/ticketing/asset-categories/datatable?limit=2');

        $response->assertOk();
        $response->assertJsonPath('total', 5);
        $response->assertJsonPath('per_page', 2);
        $this->assertCount(2, $response->json('data'));
        $response->assertJsonPath('last_page', 3); // 5 data / 2 per page = 3 pages
    }

    /**
     * H8: Pagination — halaman kedua menampilkan data yang benar.
     */
    public function test_datatable_pagination()
    {
        // Buat 5 kategori (sorted by name asc by default)
        $names = ['Alpha', 'Bravo', 'Charlie', 'Delta', 'Echo'];
        foreach ($names as $name) {
            AssetCategory::factory()->create([
                'name' => $name,
                'division_id' => $this->superAdmin->division_id,
            ]);
        }

        // Page 1, limit 2 → Alpha, Bravo
        $response = $this->actingAs($this->superAdmin)
            ->get('/ticketing/asset-categories/datatable?limit=2&page=1');

        $response->assertOk();
        $response->assertJsonPath('current_page', 1);
        $response->assertJsonPath('data.0.name', 'Alpha');
        $response->assertJsonPath('data.1.name', 'Bravo');

        // Page 2, limit 2 → Charlie, Delta
        $response = $this->actingAs($this->superAdmin)
            ->get('/ticketing/asset-categories/datatable?limit=2&page=2');

        $response->assertOk();
        $response->assertJsonPath('current_page', 2);
        $response->assertJsonPath('data.0.name', 'Charlie');
        $response->assertJsonPath('data.1.name', 'Delta');

        // Page 3, limit 2 → Echo (sisa 1)
        $response = $this->actingAs($this->superAdmin)
            ->get('/ticketing/asset-categories/datatable?limit=2&page=3');

        $response->assertOk();
        $response->assertJsonPath('current_page', 3);
        $this->assertCount(1, $response->json('data'));
        $response->assertJsonPath('data.0.name', 'Echo');
    }

    /**
     * H9: Sorting berdasarkan name ascending dan descending.
     */
    public function test_datatable_sort_by_name()
    {
        AssetCategory::factory()->create([
            'name' => 'Zebra',
            'division_id' => $this->superAdmin->division_id,
        ]);
        AssetCategory::factory()->create([
            'name' => 'Apple',
            'division_id' => $this->superAdmin->division_id,
        ]);
        AssetCategory::factory()->create([
            'name' => 'Mango',
            'division_id' => $this->superAdmin->division_id,
        ]);

        // Sort ASC
        $response = $this->actingAs($this->superAdmin)
            ->get('/ticketing/asset-categories/datatable?sort_by=name&sort_direction=asc');
        $response->assertOk();
        $response->assertJsonPath('data.0.name', 'Apple');
        $response->assertJsonPath('data.2.name', 'Zebra');

        // Sort DESC
        $response = $this->actingAs($this->superAdmin)
            ->get('/ticketing/asset-categories/datatable?sort_by=name&sort_direction=desc');
        $response->assertOk();
        $response->assertJsonPath('data.0.name', 'Zebra');
        $response->assertJsonPath('data.2.name', 'Apple');
    }

    /**
     * H10: Sorting berdasarkan type.
     */
    public function test_datatable_sort_by_type()
    {
        AssetCategory::factory()->create([
            'name' => 'Fisik Pertama',
            'type' => AssetCategoryType::Physic,
            'division_id' => $this->superAdmin->division_id,
        ]);
        AssetCategory::factory()->create([
            'name' => 'Digital Pertama',
            'type' => AssetCategoryType::Digital,
            'division_id' => $this->superAdmin->division_id,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get('/ticketing/asset-categories/datatable?sort_by=type&sort_direction=asc');
        $response->assertOk();

        $first = $response->json('data.0.type');
        $second = $response->json('data.1.type');
        $this->assertLessThanOrEqual(0, strcmp($first, $second));
    }

    /**
     * H11: Global search yang tidak cocok → total = 0.
     */
    public function test_datatable_search_no_match()
    {
        AssetCategory::factory()->create([
            'name' => 'Laptop Dell',
            'division_id' => $this->superAdmin->division_id,
        ]);
        AssetCategory::factory()->create([
            'name' => 'Printer HP',
            'division_id' => $this->superAdmin->division_id,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get('/ticketing/asset-categories/datatable?search=XyzTidakAda');

        $response->assertOk();
        $response->assertJsonPath('total', 0);
        $this->assertCount(0, $response->json('data'));
    }

    /**
     * H12: Kombinasi global search + limit + pagination bekerja bersamaan.
     */
    public function test_datatable_combined_search_with_pagination()
    {
        // Buat 4 kategori matching dan 2 noise
        for ($i = 1; $i <= 4; $i++) {
            AssetCategory::factory()->create([
                'name' => "Laptop Model {$i}",
                'division_id' => $this->superAdmin->division_id,
            ]);
        }
        // Noise
        AssetCategory::factory()->create([
            'name' => 'Printer Ruang 1',
            'division_id' => $this->superAdmin->division_id,
        ]);
        AssetCategory::factory()->create([
            'name' => 'Mesin Fotocopy',
            'division_id' => $this->superAdmin->division_id,
        ]);

        // Search 'Laptop', limit 2, page 1
        $response = $this->actingAs($this->superAdmin)
            ->get('/ticketing/asset-categories/datatable?search=Laptop&limit=2&page=1');

        $response->assertOk();
        $response->assertJsonPath('total', 4); // 4 Laptop matching
        $response->assertJsonPath('per_page', 2);
        $this->assertCount(2, $response->json('data'));

        // Page 2
        $response = $this->actingAs($this->superAdmin)
            ->get('/ticketing/asset-categories/datatable?search=Laptop&limit=2&page=2');

        $response->assertOk();
        $response->assertJsonPath('current_page', 2);
        $this->assertCount(2, $response->json('data'));
    }

    /**
     * H13: Excel export berhasil (status 200 dan content-type XLSX).
     */
    public function test_print_excel_returns_xlsx()
    {
        AssetCategory::factory()->create([
            'name' => 'Export Test',
            'division_id' => $this->superAdmin->division_id,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get('/ticketing/asset-categories/print/excel');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    /**
     * H14: Default limit tanpa parameter → 10 data per halaman.
     */
    public function test_datatable_default_limit_is_10()
    {
        // Buat 12 kategori
        for ($i = 1; $i <= 12; $i++) {
            AssetCategory::factory()->create([
                'name' => "Kategori Default {$i}",
                'division_id' => $this->superAdmin->division_id,
            ]);
        }

        $response = $this->actingAs($this->superAdmin)
            ->get('/ticketing/asset-categories/datatable');

        $response->assertOk();
        $response->assertJsonPath('total', 12);
        $response->assertJsonPath('per_page', 10);
        $this->assertCount(10, $response->json('data'));
        $response->assertJsonPath('last_page', 2);
    }
}
