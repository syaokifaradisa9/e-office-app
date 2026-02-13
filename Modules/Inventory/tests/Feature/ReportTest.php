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

describe('Division Report Page Access (/reports/division)', function () {

    /**
     * Memastikan user dengan izin 'lihat_laporan_divisi' bisa mengakses halaman laporan divisi.
     */
    it('allows user with ViewDivisionReport permission to bypass /reports/division middleware', function () {
        // 1. Persiapan user dengan izin laporan divisi
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::ViewDivisionReport->value);

        // 2. Akses halaman laporan divisi
        $response = $this->actingAs($user)->get('/inventory/reports/division');

        // 3. Validasi tidak ditolak (karena service laporan butuh MySQL DATE_FORMAT, SQLite mungkin 500 tapi bukan 403)
        expect($response->status())->not->toBe(403);
    });

    /**
     * Memastikan user dengan izin 'lihat_semua_laporan' juga bisa mengakses halaman laporan divisi.
     */
    it('allows user with ViewAllReport permission to bypass /reports/division middleware', function () {
        // 1. Persiapan user dengan izin laporan global
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::ViewAllReport->value);

        // 2. Akses halaman
        $response = $this->actingAs($user)->get('/inventory/reports/division');

        // 3. Validasi tidak ditolak
        expect($response->status())->not->toBe(403);
    });

    /**
     * Memastikan user tanpa izin apapun ditolak saat mengakses laporan divisi.
     */
    it('denies user without permission from accessing /reports/division', function () {
        // 1. Persiapan user tanpa izin
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);

        // 2. Akses ditolak (403)
        $response = $this->actingAs($user)->get('/inventory/reports/division');
        $response->assertForbidden();
    });
});

// ============================================================================
// 2. Akses /reports/all
//    Middleware: inventory_item_permission:ViewDivisionReport|ViewAllReport
//             + inventory_item_permission:ViewAllReport
// ============================================================================

describe('All Report Page Access (/reports/all)', function () {

    /**
     * Memastikan user dengan izin 'lihat_semua_laporan' dapat mengakses laporan global.
     */
    it('allows user with ViewAllReport permission to bypass /reports/all middleware', function () {
        // 1. Persiapan user dengan izin laporan global
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::ViewAllReport->value);

        // 2. Akses halaman laporan all
        $response = $this->actingAs($user)->get('/inventory/reports/all');

        // 3. Validasi tidak ditolak
        expect($response->status())->not->toBe(403);
    });

    /**
     * Memastikan user yang hanya memiliki izin 'lihat_laporan_divisi' ditolak saat mengakses laporan global.
     */
    it('denies user with ViewDivisionReport permission from accessing /reports/all', function () {
        // 1. Persiapan user dengan izin terbatas (divisi saja)
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::ViewDivisionReport->value);

        // 2. Akses ke laporan all harusnya 403
        $response = $this->actingAs($user)->get('/inventory/reports/all');
        $response->assertForbidden();
    });

    /**
     * Memastikan penolakan akses laporan global bagi user tanpa izin.
     */
    it('denies user without permission from accessing /reports/all', function () {
        // 1. Persiapan user
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);

        // 2. Akses ditolak
        $response = $this->actingAs($user)->get('/inventory/reports/all');
        $response->assertForbidden();
    });
});

// ============================================================================
// 3. Akses /reports/print-excel
//    Middleware: inventory_item_permission:ViewDivisionReport|ViewAllReport
// ============================================================================

describe('Print Excel Access (/reports/print-excel)', function () {

    /**
     * Test akses export excel laporan bagi pemegang izin divisi.
     */
    it('allows user with ViewDivisionReport permission to access /reports/print-excel', function () {
        // 1. Persiapan user
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::ViewDivisionReport->value);

        // 2. Akses print excel OK
        $response = $this->actingAs($user)->get('/inventory/reports/print-excel');
        $response->assertOk();
    });

    /**
     * Test akses export excel laporan bagi pemegang izin global.
     */
    it('allows user with ViewAllReport permission to access /reports/print-excel', function () {
        // 1. Persiapan user
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::ViewAllReport->value);

        // 2. Akses print excel OK
        $response = $this->actingAs($user)->get('/inventory/reports/print-excel');
        $response->assertOk();
    });

    /**
     * Test penolakan export excel laporan bagi user tanpa izin.
     */
    it('denies user without permission from accessing /reports/print-excel', function () {
        // 1. Persiapan user
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);

        // 2. Akses ditolak
        $response = $this->actingAs($user)->get('/inventory/reports/print-excel');
        $response->assertForbidden();
    });
});
