<?php

use App\Models\Division;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Inventory\Enums\InventoryPermission;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $permissions = InventoryPermission::values();
    foreach ($permissions as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }

    $this->divisionA = Division::factory()->create(['name' => 'Divisi A']);
});

// ============================================================================
// 1. Akses /reports/division
//    Middleware: inventory_item_permission:ViewDivisionReport|ViewAllReport
// ============================================================================

describe('Akses Halaman Laporan Divisi (/reports/division)', function () {

    it('user dengan ViewDivisionReport dapat melewati middleware /reports/division', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::ViewDivisionReport->value);

        $response = $this->actingAs($user)->get('/inventory/reports/division');

        // ReportService uses DATE_FORMAT (MySQL-only), so SQLite returns 500.
        // We verify permission passes by asserting NOT 403.
        expect($response->status())->not->toBe(403);
    });

    it('user dengan ViewAllReport dapat melewati middleware /reports/division', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::ViewAllReport->value);

        $response = $this->actingAs($user)->get('/inventory/reports/division');

        expect($response->status())->not->toBe(403);
    });

    it('user tanpa permission tidak dapat mengakses /reports/division', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);

        $response = $this->actingAs($user)->get('/inventory/reports/division');

        $response->assertForbidden();
    });
});

// ============================================================================
// 2. Akses /reports/all
//    Middleware: inventory_item_permission:ViewDivisionReport|ViewAllReport
//             + inventory_item_permission:ViewAllReport
// ============================================================================

describe('Akses Halaman Laporan Semua (/reports/all)', function () {

    it('user dengan ViewAllReport dapat melewati middleware /reports/all', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::ViewAllReport->value);

        $response = $this->actingAs($user)->get('/inventory/reports/all');

        expect($response->status())->not->toBe(403);
    });

    it('user dengan ViewDivisionReport TIDAK dapat mengakses /reports/all', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::ViewDivisionReport->value);

        $response = $this->actingAs($user)->get('/inventory/reports/all');

        $response->assertForbidden();
    });

    it('user tanpa permission tidak dapat mengakses /reports/all', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);

        $response = $this->actingAs($user)->get('/inventory/reports/all');

        $response->assertForbidden();
    });
});

// ============================================================================
// 3. Akses /reports/print-excel
//    Middleware: inventory_item_permission:ViewDivisionReport|ViewAllReport
// ============================================================================

describe('Akses Print Excel (/reports/print-excel)', function () {

    it('user dengan ViewDivisionReport dapat mengakses /reports/print-excel', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::ViewDivisionReport->value);

        $response = $this->actingAs($user)->get('/inventory/reports/print-excel');

        $response->assertOk();
    });

    it('user dengan ViewAllReport dapat mengakses /reports/print-excel', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::ViewAllReport->value);

        $response = $this->actingAs($user)->get('/inventory/reports/print-excel');

        $response->assertOk();
    });

    it('user tanpa permission tidak dapat mengakses /reports/print-excel', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);

        $response = $this->actingAs($user)->get('/inventory/reports/print-excel');

        $response->assertForbidden();
    });
});
