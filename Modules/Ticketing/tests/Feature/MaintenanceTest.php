<?php

namespace Modules\Ticketing\Tests\Feature;

use App\Models\User;
use App\Models\Division;
use Modules\Ticketing\Models\AssetCategory;
use Modules\Ticketing\Models\AssetItem;
use Modules\Ticketing\Models\Checklist;
use Modules\Ticketing\Models\Maintenance;
use Modules\Ticketing\Enums\TicketingPermission;
use Modules\Ticketing\Enums\MaintenanceStatus;
use Modules\Ticketing\Enums\AssetItemStatus;
use Modules\Ticketing\Services\AssetItemService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceTest extends TestCase
{
    use RefreshDatabase;

    protected $viewDivisionUser;
    protected $viewAllUser;
    protected $personalUser;
    protected $processUser;
    protected $confirmUser;
    protected $noPermUser;
    protected $myDivision;
    protected $otherDivision;
    protected $category;
    protected $myAsset;
    protected $otherAsset;
    protected $checklist;

    protected function setUp(): void
    {
        parent::setUp();

        // Create all Ticketing permissions
        foreach (TicketingPermission::values() as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Divisions
        $this->myDivision = Division::factory()->create(['name' => 'IT Division']);
        $this->otherDivision = Division::factory()->create(['name' => 'HR Division']);

        // Category with maintenance_count = 3
        $this->category = AssetCategory::factory()->create([
            'division_id' => $this->myDivision->id,
            'maintenance_count' => 3,
        ]);

        // Checklist items for the category
        $this->checklist = Checklist::create([
            'asset_category_id' => $this->category->id,
            'label' => 'Cek Kondisi Fisik',
            'description' => 'Periksa kondisi fisik perangkat',
        ]);

        // --- User: Lihat Jadwal Maintenance Divisi ---
        $viewDivRole = Role::firstOrCreate(['name' => 'ViewDivMaintenance', 'guard_name' => 'web']);
        $viewDivRole->syncPermissions([
            TicketingPermission::ViewDivisionMaintenance->value,
        ]);
        $this->viewDivisionUser = User::factory()->create(['division_id' => $this->myDivision->id]);
        $this->viewDivisionUser->assignRole($viewDivRole);

        // --- User: Lihat Jadwal Maintenance Keseluruhan ---
        $viewAllRole = Role::firstOrCreate(['name' => 'ViewAllMaintenance', 'guard_name' => 'web']);
        $viewAllRole->syncPermissions([
            TicketingPermission::ViewAllMaintenance->value,
        ]);
        $this->viewAllUser = User::factory()->create(['division_id' => $this->myDivision->id]);
        $this->viewAllUser->assignRole($viewAllRole);

        // --- User: Lihat Data Asset Pribadi ---
        $personalRole = Role::firstOrCreate(['name' => 'PersonalMaintenance', 'guard_name' => 'web']);
        $personalRole->syncPermissions([
            TicketingPermission::ViewPersonalAsset->value,
        ]);
        $this->personalUser = User::factory()->create(['division_id' => $this->myDivision->id]);
        $this->personalUser->assignRole($personalRole);

        // --- User: Proses Maintenance + ViewAll ---
        $processRole = Role::firstOrCreate(['name' => 'ProcessMaintenance', 'guard_name' => 'web']);
        $processRole->syncPermissions([
            TicketingPermission::ProsesMaintenance->value,
            TicketingPermission::ViewAllMaintenance->value,
        ]);
        $this->processUser = User::factory()->create(['division_id' => $this->myDivision->id]);
        $this->processUser->assignRole($processRole);

        // --- User: Konfirmasi Maintenance + ViewAll ---
        $confirmRole = Role::firstOrCreate(['name' => 'ConfirmMaintenance', 'guard_name' => 'web']);
        $confirmRole->syncPermissions([
            TicketingPermission::ConfirmMaintenance->value,
            TicketingPermission::ViewAllMaintenance->value,
        ]);
        $this->confirmUser = User::factory()->create(['division_id' => $this->myDivision->id]);
        $this->confirmUser->assignRole($confirmRole);

        // --- User: No permissions ---
        $this->noPermUser = User::factory()->create(['division_id' => $this->myDivision->id]);

        // --- Asset in my division ---
        $this->myAsset = AssetItem::factory()->create([
            'asset_category_id' => $this->category->id,
            'division_id' => $this->myDivision->id,
            'merk' => 'Dell',
            'model' => 'XPS 15',
            'serial_number' => 'SN-MY-001',
        ]);
        $this->myAsset->users()->attach($this->personalUser->id);

        // --- Asset in other division ---
        $otherCategory = AssetCategory::factory()->create([
            'division_id' => $this->otherDivision->id,
            'maintenance_count' => 2,
        ]);
        $this->otherAsset = AssetItem::factory()->create([
            'asset_category_id' => $otherCategory->id,
            'division_id' => $this->otherDivision->id,
            'merk' => 'HP',
            'model' => 'EliteBook',
            'serial_number' => 'SN-OTHER-001',
        ]);

        // Generate maintenance schedules for both assets
        app(AssetItemService::class)->generateMaintenances($this->myAsset);
        app(AssetItemService::class)->generateMaintenances($this->otherAsset);
    }

    /**
     * Helper: get the first PENDING maintenance for an asset.
     */
    private function getFirstPendingMaintenance(AssetItem $asset): Maintenance
    {
        return Maintenance::where('asset_item_id', $asset->id)
            ->where('status', MaintenanceStatus::PENDING->value)
            ->orderBy('estimation_date', 'asc')
            ->firstOrFail();
    }

    /**
     * Helper: build a valid checklist payload for store-checklist.
     */
    private function buildChecklistPayload(bool $needsFurtherRepair = false): array
    {
        return [
            'actual_date' => now()->toDateString(),
            'note' => 'Maintenance selesai tanpa kendala.',
            'needs_further_repair' => $needsFurtherRepair,
            'checklists' => [
                [
                    'checklist_id' => $this->checklist->id,
                    'label' => $this->checklist->label,
                    'description' => $this->checklist->description,
                    'value' => 'Baik',
                    'note' => null,
                    'follow_up' => null,
                ],
            ],
        ];
    }

    // =========================================================================
    // Grup A: Permission & Access Control
    // =========================================================================

    /**
     * A1: ViewDivisionMaintenance → only sees maintenances from own division.
     */
    public function test_view_division_user_only_sees_division_maintenances()
    {
        $response = $this->actingAs($this->viewDivisionUser)
            ->get('/ticketing/maintenances/datatable');

        $response->assertOk();

        // Should only have maintenances from myDivision asset
        $data = $response->json('data');
        foreach ($data as $item) {
            $assetItem = AssetItem::find($item['asset_item']['id']);
            $this->assertEquals($this->myDivision->id, $assetItem->division_id);
        }

        // Should NOT include maintenance from otherDivision
        $otherMaintenanceIds = Maintenance::where('asset_item_id', $this->otherAsset->id)->pluck('id')->toArray();
        $returnedIds = collect($data)->pluck('id')->toArray();
        $this->assertEmpty(array_intersect($otherMaintenanceIds, $returnedIds));
    }

    /**
     * A2: ViewAllMaintenance → sees maintenances from all divisions.
     */
    public function test_view_all_user_sees_all_maintenances()
    {
        $response = $this->actingAs($this->viewAllUser)
            ->get('/ticketing/maintenances/datatable');

        $response->assertOk();

        // Total should include both myAsset and otherAsset maintenances
        $myCount = Maintenance::where('asset_item_id', $this->myAsset->id)->count();
        $otherCount = Maintenance::where('asset_item_id', $this->otherAsset->id)->count();

        $this->assertEquals($myCount + $otherCount, $response->json('total'));
    }

    /**
     * A3: ViewPersonalAsset → only sees maintenances from own assigned assets.
     */
    public function test_personal_user_only_sees_own_asset_maintenances()
    {
        $response = $this->actingAs($this->personalUser)
            ->get('/ticketing/maintenances/datatable');

        $response->assertOk();

        // Should only have maintenances from myAsset (assigned to personalUser)
        $returnedIds = collect($response->json('data'))->pluck('asset_item.id')->unique()->toArray();
        $this->assertEquals([$this->myAsset->id], array_values($returnedIds));
    }

    /**
     * A4: No permission → 403 on index.
     */
    public function test_no_permission_user_gets_403_on_index()
    {
        $this->actingAs($this->noPermUser)
            ->get('/ticketing/maintenances')
            ->assertForbidden();
    }

    /**
     * A5: No permission → 403 on datatable.
     */
    public function test_no_permission_user_gets_403_on_datatable()
    {
        $this->actingAs($this->noPermUser)
            ->get('/ticketing/maintenances/datatable')
            ->assertForbidden();
    }

    /**
     * A6: No permission → 403 on detail.
     */
    public function test_no_permission_user_gets_403_on_detail()
    {
        $maintenance = $this->getFirstPendingMaintenance($this->myAsset);
        $this->actingAs($this->noPermUser)
            ->get("/ticketing/maintenances/{$maintenance->id}/detail")
            ->assertForbidden();
    }

    /**
     * A7: No ProsesMaintenance permission → 403 on process.
     */
    public function test_no_permission_user_gets_403_on_process()
    {
        $maintenance = $this->getFirstPendingMaintenance($this->myAsset);
        $this->actingAs($this->noPermUser)
            ->get("/ticketing/maintenances/{$maintenance->id}/process")
            ->assertForbidden();
    }

    // =========================================================================
    // Grup B: Proses Checklist / Process Maintenance
    // =========================================================================

    /**
     * B1: Process page accessible for user with ProsesMaintenance permission.
     */
    public function test_process_page_accessible_for_process_user()
    {
        $maintenance = $this->getFirstPendingMaintenance($this->myAsset);

        $response = $this->actingAs($this->processUser)
            ->get("/ticketing/maintenances/{$maintenance->id}/process");

        $response->assertOk();
    }

    /**
     * B2: Store checklist without further repair → status FINISH, asset Available.
     */
    public function test_store_checklist_without_further_repair_sets_status_finish()
    {
        $maintenance = $this->getFirstPendingMaintenance($this->myAsset);
        $payload = $this->buildChecklistPayload(needsFurtherRepair: false);

        $response = $this->actingAs($this->processUser)
            ->post("/ticketing/maintenances/{$maintenance->id}/store-checklist", $payload);

        $response->assertRedirect();

        // Maintenance status should be FINISH
        $maintenance->refresh();
        $this->assertEquals(MaintenanceStatus::FINISH, $maintenance->status);

        // Asset status should be Available
        $this->myAsset->refresh();
        $this->assertEquals(AssetItemStatus::Available, $this->myAsset->status);
    }

    /**
     * B3: Store checklist with further repair → status REFINEMENT, asset Refinement.
     */
    public function test_store_checklist_with_further_repair_sets_status_refinement()
    {
        $maintenance = $this->getFirstPendingMaintenance($this->myAsset);
        $payload = $this->buildChecklistPayload(needsFurtherRepair: true);

        $response = $this->actingAs($this->processUser)
            ->post("/ticketing/maintenances/{$maintenance->id}/store-checklist", $payload);

        $response->assertRedirect();

        // Maintenance status should be REFINEMENT
        $maintenance->refresh();
        $this->assertEquals(MaintenanceStatus::REFINEMENT, $maintenance->status);

        // Asset status should be Refinement
        $this->myAsset->refresh();
        $this->assertEquals(AssetItemStatus::Refinement, $this->myAsset->status);
    }

    /**
     * B4: Store checklist saves checklist results to maintenance_checklists table.
     */
    public function test_store_checklist_saves_checklist_results()
    {
        $maintenance = $this->getFirstPendingMaintenance($this->myAsset);
        $payload = $this->buildChecklistPayload();

        $this->actingAs($this->processUser)
            ->post("/ticketing/maintenances/{$maintenance->id}/store-checklist", $payload);

        // Checklist result should be saved in maintenance_checklists table
        $this->assertDatabaseHas('maintenance_checklists', [
            'maintenance_id' => $maintenance->id,
            'checklist_id' => $this->checklist->id,
            'label' => $this->checklist->label,
            'value' => 'Good', // 'Baik' → stored as 'Good'
        ]);
    }

    /**
     * B5: Store checklist saves actual_date and note.
     */
    public function test_store_checklist_saves_actual_date_and_note()
    {
        $maintenance = $this->getFirstPendingMaintenance($this->myAsset);
        $payload = $this->buildChecklistPayload();

        $this->actingAs($this->processUser)
            ->post("/ticketing/maintenances/{$maintenance->id}/store-checklist", $payload);

        $maintenance->refresh();
        $this->assertEquals(now()->toDateString(), $maintenance->actual_date->toDateString());
        $this->assertEquals('Maintenance selesai tanpa kendala.', $maintenance->note);
        $this->assertEquals($this->processUser->id, $maintenance->user_id);
    }

    /**
     * B6: Store checklist blocked if previous maintenance not confirmed.
     */
    public function test_store_checklist_blocked_if_previous_maintenance_not_confirmed()
    {
        // Get all PENDING maintenances sorted by estimation_date
        $maintenances = Maintenance::where('asset_item_id', $this->myAsset->id)
            ->where('status', MaintenanceStatus::PENDING->value)
            ->orderBy('estimation_date', 'asc')
            ->get();

        if ($maintenances->count() < 2) {
            $this->markTestSkipped('Need at least 2 pending maintenances for this test.');
        }

        // Process the first one to FINISH (not CONFIRMED yet)
        $first = $maintenances->first();
        $first->update([
            'status' => MaintenanceStatus::FINISH,
            'actual_date' => now()->toDateString(),
        ]);

        // Try to process the second one → should be blocked (prior not CONFIRMED)
        $second = $maintenances->get(1);
        $payload = $this->buildChecklistPayload();

        $response = $this->actingAs($this->processUser)
            ->post("/ticketing/maintenances/{$second->id}/store-checklist", $payload);

        $response->assertForbidden();
    }

    // =========================================================================
    // Grup C: Konfirmasi Maintenance
    // =========================================================================

    /**
     * C1: Confirm changes status from FINISH to CONFIRMED.
     */
    public function test_confirm_changes_status_to_confirmed()
    {
        $maintenance = $this->getFirstPendingMaintenance($this->myAsset);
        $maintenance->update([
            'status' => MaintenanceStatus::FINISH,
            'actual_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->confirmUser)
            ->post("/ticketing/maintenances/{$maintenance->id}/confirm");

        $response->assertRedirect();

        $maintenance->refresh();
        $this->assertEquals(MaintenanceStatus::CONFIRMED, $maintenance->status);
    }

    /**
     * C2: Confirm fails if status is not FINISH (e.g. PENDING).
     */
    public function test_confirm_fails_if_status_is_not_finish()
    {
        $maintenance = $this->getFirstPendingMaintenance($this->myAsset);
        // Status is still PENDING

        $response = $this->actingAs($this->confirmUser)
            ->post("/ticketing/maintenances/{$maintenance->id}/confirm");

        // Should redirect back with error flash
        $response->assertRedirect();
        $response->assertSessionHas('flash.type', 'danger');

        // Status should still be PENDING
        $maintenance->refresh();
        $this->assertEquals(MaintenanceStatus::PENDING, $maintenance->status);
    }

    /**
     * C3: Confirm requires ConfirmMaintenance permission.
     */
    public function test_confirm_requires_confirm_permission()
    {
        $maintenance = $this->getFirstPendingMaintenance($this->myAsset);
        $maintenance->update([
            'status' => MaintenanceStatus::FINISH,
            'actual_date' => now()->toDateString(),
        ]);

        // User without ConfirmMaintenance permission → 403
        $this->actingAs($this->processUser)
            ->post("/ticketing/maintenances/{$maintenance->id}/confirm")
            ->assertForbidden();
    }

    /**
     * C4: Confirm fails when status is REFINEMENT.
     */
    public function test_confirm_refinement_status_fails()
    {
        $maintenance = $this->getFirstPendingMaintenance($this->myAsset);
        $maintenance->update([
            'status' => MaintenanceStatus::REFINEMENT,
            'actual_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->confirmUser)
            ->post("/ticketing/maintenances/{$maintenance->id}/confirm");

        $response->assertRedirect();
        $response->assertSessionHas('flash.type', 'danger');

        $maintenance->refresh();
        $this->assertEquals(MaintenanceStatus::REFINEMENT, $maintenance->status);
    }

    // =========================================================================
    // Grup D: Datatable — Search, Sort, Pagination
    // =========================================================================

    /**
     * D1: Global search by merk.
     */
    public function test_datatable_global_search_by_merk()
    {
        $response = $this->actingAs($this->viewAllUser)
            ->get('/ticketing/maintenances/datatable?search=Dell');

        $response->assertOk();

        // All results should be from Dell asset
        $data = $response->json('data');
        foreach ($data as $item) {
            $this->assertEquals('Dell', $item['asset_item']['merk']);
        }

        // Total should match myAsset maintenance count (Dell)
        $expectedCount = Maintenance::where('asset_item_id', $this->myAsset->id)->count();
        $this->assertEquals($expectedCount, $response->json('total'));
    }

    /**
     * D2: Global search by model.
     */
    public function test_datatable_global_search_by_model()
    {
        $response = $this->actingAs($this->viewAllUser)
            ->get('/ticketing/maintenances/datatable?search=EliteBook');

        $response->assertOk();

        // All results should be from EliteBook (otherAsset)
        $data = $response->json('data');
        foreach ($data as $item) {
            $this->assertEquals('EliteBook', $item['asset_item']['model']);
        }
    }

    /**
     * D3: Global search by serial_number.
     */
    public function test_datatable_global_search_by_serial_number()
    {
        $response = $this->actingAs($this->viewAllUser)
            ->get('/ticketing/maintenances/datatable?search=SN-MY-001');

        $response->assertOk();

        $data = $response->json('data');
        foreach ($data as $item) {
            $this->assertEquals('SN-MY-001', $item['asset_item']['serial_number']);
        }
    }

    /**
     * D4: Global search by category name.
     */
    public function test_datatable_global_search_by_category_name()
    {
        $response = $this->actingAs($this->viewAllUser)
            ->get('/ticketing/maintenances/datatable?search=' . urlencode($this->category->name));

        $response->assertOk();

        $data = $response->json('data');
        foreach ($data as $item) {
            $this->assertEquals($this->category->name, $item['asset_item']['category_name']);
        }
    }

    /**
     * D5: Filter by year.
     */
    public function test_datatable_filter_by_year()
    {
        $targetYear = Maintenance::where('asset_item_id', $this->myAsset->id)
            ->orderBy('estimation_date', 'asc')
            ->first()
            ->estimation_date
            ->year;

        $response = $this->actingAs($this->viewAllUser)
            ->get("/ticketing/maintenances/datatable?year={$targetYear}");

        $response->assertOk();

        // All results should have estimation_date in the target year
        $data = $response->json('data');
        foreach ($data as $item) {
            $year = \Illuminate\Support\Carbon::parse($item['estimation_date'])->year;
            $this->assertEquals($targetYear, $year);
        }
    }

    /**
     * D6: Sort by estimation_date descending.
     */
    public function test_datatable_sort_by_estimation_date()
    {
        $response = $this->actingAs($this->viewAllUser)
            ->get('/ticketing/maintenances/datatable?sort_by=estimation_date&sort_direction=desc');

        $response->assertOk();

        $data = $response->json('data');
        if (count($data) >= 2) {
            // First item should have a later or equal estimation_date than the second
            $this->assertGreaterThanOrEqual(
                $data[1]['estimation_date'],
                $data[0]['estimation_date']
            );
        }
    }

    /**
     * D7: Pagination limit.
     */
    public function test_datatable_pagination_limit()
    {
        $response = $this->actingAs($this->viewAllUser)
            ->get('/ticketing/maintenances/datatable?limit=2');

        $response->assertOk();
        $response->assertJsonPath('per_page', 2);
        $this->assertLessThanOrEqual(2, count($response->json('data')));

        // Total should reflect all maintenances, not just the page
        $totalMaintenances = Maintenance::count();
        $this->assertEquals($totalMaintenances, $response->json('total'));
    }

    // =========================================================================
    // Grup E: Export Excel
    // =========================================================================

    /**
     * E1: Print Excel returns XLSX file.
     */
    public function test_print_excel_returns_xlsx()
    {
        $response = $this->actingAs($this->viewAllUser)
            ->get('/ticketing/maintenances/print/excel');

        $response->assertOk();
        $response->assertHeader(
            'content-type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
    }

    /**
     * E2: Print Excel requires view permission.
     */
    public function test_print_excel_requires_view_permission()
    {
        $this->actingAs($this->noPermUser)
            ->get('/ticketing/maintenances/print/excel')
            ->assertForbidden();
    }

    // =========================================================================
    // Grup F: Actionable Logic
    // =========================================================================

    /**
     * F1: Maintenance is actionable when no prior unconfirmed maintenance exists.
     */
    public function test_maintenance_is_actionable_when_no_prior_unconfirmed()
    {
        // The first PENDING maintenance should be actionable
        $response = $this->actingAs($this->viewAllUser)
            ->get('/ticketing/maintenances/datatable');

        $response->assertOk();

        // Find the earliest maintenance by estimation_date for myAsset
        $data = collect($response->json('data'));
        $myAssetData = $data->filter(fn ($item) => $item['asset_item']['id'] === $this->myAsset->id)
            ->sortBy('estimation_date')
            ->values();

        if ($myAssetData->isNotEmpty()) {
            $this->assertTrue($myAssetData->first()['is_actionable']);
        }
    }

    /**
     * F2: Maintenance is NOT actionable when prior maintenance is not confirmed.
     */
    public function test_maintenance_not_actionable_when_prior_not_confirmed()
    {
        $maintenances = Maintenance::where('asset_item_id', $this->myAsset->id)
            ->where('status', MaintenanceStatus::PENDING->value)
            ->orderBy('estimation_date', 'asc')
            ->get();

        if ($maintenances->count() < 2) {
            $this->markTestSkipped('Need at least 2 pending maintenances for this test.');
        }

        // Set the first maintenance to FINISH (not confirmed yet)
        $maintenances->first()->update([
            'status' => MaintenanceStatus::FINISH,
            'actual_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->viewAllUser)
            ->get('/ticketing/maintenances/datatable');

        $response->assertOk();

        // The second maintenance should NOT be actionable
        $data = collect($response->json('data'));
        $second = $data->firstWhere('id', $maintenances->get(1)->id);
        $this->assertNotNull($second);
        $this->assertFalse($second['is_actionable']);
    }

    /**
     * F3: Maintenance becomes actionable after prior maintenance is confirmed.
     */
    public function test_maintenance_becomes_actionable_after_prior_confirmed()
    {
        $maintenances = Maintenance::where('asset_item_id', $this->myAsset->id)
            ->where('status', MaintenanceStatus::PENDING->value)
            ->orderBy('estimation_date', 'asc')
            ->get();

        if ($maintenances->count() < 2) {
            $this->markTestSkipped('Need at least 2 pending maintenances for this test.');
        }

        // Set the first maintenance to CONFIRMED
        $maintenances->first()->update([
            'status' => MaintenanceStatus::CONFIRMED,
            'actual_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->viewAllUser)
            ->get('/ticketing/maintenances/datatable');

        $response->assertOk();

        // The second maintenance should now be actionable
        $data = collect($response->json('data'));
        $second = $data->firstWhere('id', $maintenances->get(1)->id);
        $this->assertNotNull($second);
        $this->assertTrue($second['is_actionable']);
    }
}
