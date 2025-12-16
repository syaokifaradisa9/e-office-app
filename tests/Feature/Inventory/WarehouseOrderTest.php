<?php

use App\Models\Division;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Inventory\Enums\WarehouseOrderStatus;
use Modules\Inventory\Models\CategoryItem;
use Modules\Inventory\Models\Item;
use Modules\Inventory\Models\WarehouseOrder;
use Modules\Inventory\Models\WarehouseOrderCart;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create permissions
    $permissions = [
        'lihat_permintaan_barang',
        'lihat_semua_permintaan_barang',
        'buat_permintaan_barang',
        'konfirmasi_permintaan_barang',
        'serah_terima_barang',
        'terima_barang',
    ];

    foreach ($permissions as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }
});

it('can display warehouse order index for authorized user', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('lihat_permintaan_barang');

    $response = $this->actingAs($user)->get('/inventory/warehouse-orders');

    $response->assertOk();
});

it('denies access for unauthorized user', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/inventory/warehouse-orders');

    $response->assertForbidden();
});

it('can create a warehouse order', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('buat_permintaan_barang');

    $division = Division::factory()->create();
    $category = CategoryItem::create(['name' => 'Test', 'is_active' => true]);
    $item = Item::create([
        'category_id' => $category->id,
        'name' => 'Test Item',
        'unit_of_measure' => 'pcs',
        'stock' => 100,
    ]);

    $response = $this->actingAs($user)->post('/inventory/warehouse-orders/store', [
        'division_id' => $division->id,
        'description' => 'Test order',
        'notes' => 'Some notes',
        'items' => [
            ['item_id' => $item->id, 'quantity' => 5],
        ],
    ]);

    $response->assertRedirect('/inventory/warehouse-orders');
    $this->assertDatabaseHas('warehouse_orders', [
        'user_id' => $user->id,
        'division_id' => $division->id,
        'status' => WarehouseOrderStatus::Pending->value,
    ]);
});

it('validates required fields when creating order', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('buat_permintaan_barang');

    $response = $this->actingAs($user)->post('/inventory/warehouse-orders/store', []);

    $response->assertSessionHasErrors(['division_id', 'items']);
});

it('can confirm a pending order', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('konfirmasi_permintaan_barang');

    $division = Division::factory()->create();
    $order = WarehouseOrder::create([
        'user_id' => $user->id,
        'division_id' => $division->id,
        'order_number' => 'WO-TEST-001',
        'status' => WarehouseOrderStatus::Pending,
    ]);

    $response = $this->actingAs($user)->patch("/inventory/warehouse-orders/{$order->id}/confirm");

    $response->assertRedirect();
    $this->assertDatabaseHas('warehouse_orders', [
        'id' => $order->id,
        'status' => WarehouseOrderStatus::Confirmed->value,
    ]);
});

it('can reject a pending order', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('konfirmasi_permintaan_barang');

    $division = Division::factory()->create();
    $order = WarehouseOrder::create([
        'user_id' => $user->id,
        'division_id' => $division->id,
        'order_number' => 'WO-TEST-002',
        'status' => WarehouseOrderStatus::Pending,
    ]);

    $response = $this->actingAs($user)->post("/inventory/warehouse-orders/{$order->id}/reject", [
        'reason' => 'Item tidak tersedia',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('warehouse_orders', [
        'id' => $order->id,
        'status' => WarehouseOrderStatus::Rejected->value,
    ]);
});

it('cannot edit confirmed order', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('buat_permintaan_barang');

    $division = Division::factory()->create();
    $order = WarehouseOrder::create([
        'user_id' => $user->id,
        'division_id' => $division->id,
        'order_number' => 'WO-TEST-003',
        'status' => WarehouseOrderStatus::Confirmed,
    ]);

    $response = $this->actingAs($user)->get("/inventory/warehouse-orders/{$order->id}/edit");

    $response->assertForbidden();
});

it('can deliver confirmed order', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('serah_terima_barang');

    $division = Division::factory()->create();
    $category = CategoryItem::create(['name' => 'Test', 'is_active' => true]);
    $item = Item::create([
        'category_id' => $category->id,
        'name' => 'Test Item',
        'unit_of_measure' => 'pcs',
        'stock' => 100,
    ]);

    $order = WarehouseOrder::create([
        'user_id' => $user->id,
        'division_id' => $division->id,
        'order_number' => 'WO-TEST-004',
        'status' => WarehouseOrderStatus::Confirmed,
    ]);

    $cart = WarehouseOrderCart::create([
        'warehouse_order_id' => $order->id,
        'item_id' => $item->id,
        'quantity' => 5,
    ]);

    $response = $this->actingAs($user)->post("/inventory/warehouse-orders/{$order->id}/delivery", [
        'items' => [
            ['cart_id' => $cart->id, 'delivered_quantity' => 5],
        ],
    ]);

    $response->assertRedirect('/inventory/warehouse-orders');
    $this->assertDatabaseHas('warehouse_orders', [
        'id' => $order->id,
        'status' => WarehouseOrderStatus::Delivered->value,
    ]);
});

it('can receive delivered order', function () {
    $division = Division::factory()->create();
    $user = User::factory()->create(['division_id' => $division->id]);
    $user->givePermissionTo('terima_barang');

    $category = CategoryItem::create(['name' => 'Test', 'is_active' => true]);
    $item = Item::create([
        'category_id' => $category->id,
        'name' => 'Test Item',
        'unit_of_measure' => 'pcs',
        'stock' => 100,
    ]);

    $order = WarehouseOrder::create([
        'user_id' => $user->id,
        'division_id' => $division->id,
        'order_number' => 'WO-TEST-005',
        'status' => WarehouseOrderStatus::Delivered,
        'delivered_by' => $user->id,
        'delivery_date' => now(),
    ]);

    $cart = WarehouseOrderCart::create([
        'warehouse_order_id' => $order->id,
        'item_id' => $item->id,
        'quantity' => 5,
        'delivered_quantity' => 5,
    ]);

    $response = $this->actingAs($user)->post("/inventory/warehouse-orders/{$order->id}/receive", [
        'items' => [
            ['cart_id' => $cart->id, 'received_quantity' => 5],
        ],
    ]);

    $response->assertRedirect('/inventory/warehouse-orders');
    $this->assertDatabaseHas('warehouse_orders', [
        'id' => $order->id,
        'status' => WarehouseOrderStatus::Finished->value,
    ]);
});

it('returns datatable data with permission filter', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('lihat_permintaan_barang');

    $response = $this->actingAs($user)->get('/inventory/warehouse-orders/datatable');

    $response->assertOk();
});

it('exports excel successfully', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('lihat_semua_permintaan_barang');

    $response = $this->actingAs($user)->get('/inventory/warehouse-orders/print-excel');

    $response->assertOk();
});
