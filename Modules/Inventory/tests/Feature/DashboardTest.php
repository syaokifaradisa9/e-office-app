<?php

use App\Models\Division;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Inventory\Enums\InventoryPermission;
use Modules\Inventory\Enums\WarehouseOrderStatus;
use Modules\Inventory\Models\WarehouseOrder;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $permissions = [
        InventoryPermission::ViewMainWarehouseDashboard->value,
        InventoryPermission::ViewDivisionWarehouseDashboard->value,
    ];

    foreach ($permissions as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }
});

/**
 * Memastikan user yang memiliki izin dashboard gudang utama otomatis dialihkan ke halaman yang benar.
 */
it('redirects to main warehouse dashboard for authorized user', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(InventoryPermission::ViewMainWarehouseDashboard->value);

    $response = $this->actingAs($user)->get('/inventory/dashboard');

    $response->assertRedirect('/inventory/dashboard/main-warehouse');
});

/**
 * Memastikan user yang memiliki izin dashboard gudang divisi otomatis dialihkan ke halaman yang benar.
 */
it('redirects to division warehouse dashboard for authorized user', function () {
    $division = Division::factory()->create();
    $user = User::factory()->create(['division_id' => $division->id]);
    $user->givePermissionTo(InventoryPermission::ViewDivisionWarehouseDashboard->value);

    $response = $this->actingAs($user)->get('/inventory/dashboard');

    $response->assertRedirect('/inventory/dashboard/division-warehouse');
});

/**
 * Memastikan akses ke halaman dashboard gudang utama berhasil bagi user berwenang.
 */
it('can access main warehouse dashboard', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(InventoryPermission::ViewMainWarehouseDashboard->value);

    $response = $this->actingAs($user)->get('/inventory/dashboard/main-warehouse');

    $response->assertStatus(200);
    expect($response->inertiaPage()['component'])->toBe('Inventory/Dashboard/MainWarehouse');
});

/**
 * Memastikan user tanpa izin 'lihat_dashboard_gudang_utama' dilarang mengakses halaman tsb.
 */
it('denies main warehouse dashboard for unauthorized user', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/inventory/dashboard/main-warehouse');

    $response->assertForbidden();
});

/**
 * Memastikan akses ke halaman dashboard gudang divisi berhasil bagi user berwenang.
 */
it('can access division warehouse dashboard', function () {
    $division = Division::factory()->create();
    $user = User::factory()->create(['division_id' => $division->id]);
    $user->givePermissionTo(InventoryPermission::ViewDivisionWarehouseDashboard->value);

    $response = $this->actingAs($user)->get('/inventory/dashboard/division-warehouse');

    $response->assertStatus(200);
    expect($response->inertiaPage()['component'])->toBe('Inventory/Dashboard/DivisionWarehouse');
});

/**
 * Memastikan user tanpa izin 'lihat_dashboard_gudang_divisi' dilarang mengakses halaman tsb.
 */
it('denies division warehouse dashboard for unauthorized user', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/inventory/dashboard/division-warehouse');

    $response->assertForbidden();
});

/**
 * Memastikan daftar permintaan barang yang masih 'Pending' muncul pada dashboard gudang utama.
 */
it('shows pending orders on main warehouse dashboard', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(InventoryPermission::ViewMainWarehouseDashboard->value);

    $division = Division::factory()->create();
    WarehouseOrder::create([
        'user_id' => $user->id,
        'division_id' => $division->id,
        'order_number' => 'WO-TEST-001',
        'status' => WarehouseOrderStatus::Pending,
    ]);

    $response = $this->actingAs($user)->get('/inventory/dashboard/main-warehouse');

    $response->assertOk();
    $page = $response->inertiaPage();
    expect($page['props']['pendingOrders'])->toHaveCount(1);
    expect($page['props'])->toHaveKey('statistics');
});

/**
 * Memastikan daftar permintaan barang yang sudah 'Confirmed' muncul pada dashboard gudang utama.
 */
it('shows confirmed orders on main warehouse dashboard', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(InventoryPermission::ViewMainWarehouseDashboard->value);

    $division = Division::factory()->create();
    WarehouseOrder::create([
        'user_id' => $user->id,
        'division_id' => $division->id,
        'order_number' => 'WO-TEST-002',
        'status' => WarehouseOrderStatus::Confirmed,
    ]);

    $response = $this->actingAs($user)->get('/inventory/dashboard/main-warehouse');

    $response->assertOk();
    expect($response->inertiaPage()['props']['confirmedOrders'])->toHaveCount(1);
});

/**
 * Memastikan daftar permintaan barang yang sudah 'Delivered' muncul pada dashboard gudang divisi.
 */
it('shows delivered orders on division warehouse dashboard', function () {
    $division = Division::factory()->create();
    $user = User::factory()->create(['division_id' => $division->id]);
    $user->givePermissionTo(InventoryPermission::ViewDivisionWarehouseDashboard->value);

    WarehouseOrder::create([
        'user_id' => $user->id,
        'division_id' => $division->id,
        'order_number' => 'WO-TEST-003',
        'status' => WarehouseOrderStatus::Delivered,
    ]);

    $response = $this->actingAs($user)->get('/inventory/dashboard/division-warehouse');

    $response->assertOk();
    $response->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page->has('active_orders', 1));
});

/**
 * Memastikan muncul pesan error informatif jika user mencoba akses dashboard divisi tapi belum masuk divisi manapun.
 */
it('shows error message for division user without division', function () {
    $user = User::factory()->create(['division_id' => null]);
    $user->givePermissionTo(InventoryPermission::ViewDivisionWarehouseDashboard->value);

    $response = $this->actingAs($user)->get('/inventory/dashboard/division-warehouse');

    $response->assertOk();
    expect($response->inertiaPage()['props'])->toHaveKey('error');
});

/**
 * Memastikan data pada dashboard divisi benar-benar terfilter hanya untuk divisi user yang bersangkutan.
 */
it('filters division dashboard by user division', function () {
    $division1 = Division::factory()->create();
    $division2 = Division::factory()->create();

    $user = User::factory()->create(['division_id' => $division1->id]);
    $user->givePermissionTo(InventoryPermission::ViewDivisionWarehouseDashboard->value);

    // Create order for user's division
    WarehouseOrder::create([
        'user_id' => $user->id,
        'division_id' => $division1->id,
        'order_number' => 'WO-001',
        'status' => WarehouseOrderStatus::Delivered,
    ]);

    // Create order for different division (should not appear)
    WarehouseOrder::create([
        'user_id' => $user->id,
        'division_id' => $division2->id,
        'order_number' => 'WO-002',
        'status' => WarehouseOrderStatus::Delivered,
    ]);

    $response = $this->actingAs($user)->get('/inventory/dashboard/division-warehouse');

    $response->assertOk();
    $response->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page->has('active_orders', 1));
});
