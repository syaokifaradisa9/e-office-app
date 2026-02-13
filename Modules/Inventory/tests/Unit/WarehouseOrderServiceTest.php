<?php

namespace Modules\Inventory\Tests\Unit;

use App\Models\Division;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Inventory\Enums\InventoryPermission;
use Modules\Inventory\Enums\WarehouseOrderStatus;
use Modules\Inventory\Models\CategoryItem;
use Modules\Inventory\Models\Item;
use Modules\Inventory\Models\WarehouseOrder;
use Modules\Inventory\Models\WarehouseOrderCart;
use Modules\Inventory\DataTransferObjects\WarehouseOrderDTO;
use Modules\Inventory\Services\WarehouseOrderService;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(WarehouseOrderService::class);

    $permissions = InventoryPermission::values();
    foreach ($permissions as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }
});

/**
 * Memastikan service dapat membuat pesanan gudang baru dengan status awal 'Pending'.
 */
it('can create a warehouse order', function () {
    $user = User::factory()->create();
    $division = Division::factory()->create();
    $category = CategoryItem::create(['name' => 'Test', 'is_active' => true]);
    $item = Item::create([
        'category_id' => $category->id,
        'name' => 'Test Item',
        'unit_of_measure' => 'pcs',
        'stock' => 100,
    ]);

    $data = [
        'division_id' => $division->id,
        'description' => 'Test order',
        'items' => [
            ['item_id' => $item->id, 'quantity' => 5],
        ],
    ];

    $order = $this->service->store(new WarehouseOrderDTO(
        description: $data['description'],
        notes: null,
        items: $data['items'],
        division_id: $data['division_id']
    ), $user);

    expect($order)->toBeInstanceOf(WarehouseOrder::class);
    expect($order->user_id)->toBe($user->id);
    expect($order->status)->toBe(WarehouseOrderStatus::Pending);
    expect($order->carts)->toHaveCount(1);
});

/**
 * Memastikan service dapat memperbarui data pesanan gudang yang sudah ada (termasuk item di dalamnya).
 */
it('can update a warehouse order', function () {
    $user = User::factory()->create();
    $division = Division::factory()->create();
    $category = CategoryItem::create(['name' => 'Test', 'is_active' => true]);
    $item1 = Item::create([
        'category_id' => $category->id,
        'name' => 'Item 1',
        'unit_of_measure' => 'pcs',
        'stock' => 100,
    ]);
    $item2 = Item::create([
        'category_id' => $category->id,
        'name' => 'Item 2',
        'unit_of_measure' => 'pcs',
        'stock' => 50,
    ]);

    $order = WarehouseOrder::create([
        'user_id' => $user->id,
        'division_id' => $division->id,
        'order_number' => 'WO-TEST-001',
        'status' => WarehouseOrderStatus::Pending,
    ]);

    WarehouseOrderCart::create([
        'warehouse_order_id' => $order->id,
        'item_id' => $item1->id,
        'quantity' => 5,
    ]);

    $data = [
        'division_id' => $division->id,
        'description' => 'Updated description',
        'items' => [
            ['item_id' => $item2->id, 'quantity' => 10],
        ],
    ];

    $updated = $this->service->update($order, $data);

    expect($updated->description)->toBe('Updated description');
    expect($updated->carts)->toHaveCount(1);
    expect($updated->carts->first()->item_id)->toBe($item2->id);
});

/**
 * Memastikan service dapat memproses konfirmasi pesanan yang statusnya masih 'Pending'.
 */
it('can confirm a pending order', function () {
    $user = User::factory()->create();
    $division = Division::factory()->create();

    $order = WarehouseOrder::create([
        'user_id' => $user->id,
        'division_id' => $division->id,
        'order_number' => 'WO-TEST-002',
        'status' => WarehouseOrderStatus::Pending,
    ]);

    $confirmed = $this->service->confirm($order);

    expect($confirmed->status)->toBe(WarehouseOrderStatus::Confirmed);
    expect($confirmed->accepted_date)->not->toBeNull();
});

/**
 * Memastikan service dapat menolak pesanan dengan alasan penolakan yang dicatat di database.
 */
it('can reject an order with reason', function () {
    $user = User::factory()->create();
    $division = Division::factory()->create();

    $order = WarehouseOrder::create([
        'user_id' => $user->id,
        'division_id' => $division->id,
        'order_number' => 'WO-TEST-003',
        'status' => WarehouseOrderStatus::Pending,
    ]);

    $rejected = $this->service->reject($order, 'Stock tidak tersedia', $user);

    expect($rejected->status)->toBe(WarehouseOrderStatus::Rejected);
    $this->assertDatabaseHas('warehouse_order_rejects', [
        'warehouse_order_id' => $order->id,
        'reason' => 'Stock tidak tersedia',
    ]);
});

/**
 * Memastikan validasi fungsional (canEdit) hanya memperbolehkan pengubahan data pada pesanan berstatus 'Pending'.
 */
it('checks if order can be edited', function () {
    $user = User::factory()->create();
    $division = Division::factory()->create();

    $pendingOrder = WarehouseOrder::create([
        'user_id' => $user->id,
        'division_id' => $division->id,
        'order_number' => 'WO-001',
        'status' => WarehouseOrderStatus::Pending,
    ]);

    $confirmedOrder = WarehouseOrder::create([
        'user_id' => $user->id,
        'division_id' => $division->id,
        'order_number' => 'WO-002',
        'status' => WarehouseOrderStatus::Confirmed,
    ]);

    expect($this->service->canEdit($pendingOrder))->toBeTrue();
    expect($this->service->canEdit($confirmedOrder))->toBeFalse();
});

/**
 * Memastikan validasi fungsional (canConfirm) hanya memperbolehkan konfirmasi pada pesanan yang belum diproses.
 */
it('checks if order can be confirmed', function () {
    $user = User::factory()->create();
    $division = Division::factory()->create();

    $pendingOrder = WarehouseOrder::create([
        'user_id' => $user->id,
        'division_id' => $division->id,
        'order_number' => 'WO-001',
        'status' => WarehouseOrderStatus::Pending,
    ]);

    $deliveredOrder = WarehouseOrder::create([
        'user_id' => $user->id,
        'division_id' => $division->id,
        'order_number' => 'WO-002',
        'status' => WarehouseOrderStatus::Delivered,
    ]);

    expect($this->service->canConfirm($pendingOrder))->toBeTrue();
    expect($this->service->canConfirm($deliveredOrder))->toBeFalse();
});

/**
 * Memastikan validasi fungsional (canDeliver) hanya memperbolehkan pengiriman pada pesanan yang sudah dikonfirmasi.
 */
it('checks if order can be delivered', function () {
    $user = User::factory()->create();
    $division = Division::factory()->create();

    $confirmedOrder = WarehouseOrder::create([
        'user_id' => $user->id,
        'division_id' => $division->id,
        'order_number' => 'WO-001',
        'status' => WarehouseOrderStatus::Confirmed,
    ]);

    $pendingOrder = WarehouseOrder::create([
        'user_id' => $user->id,
        'division_id' => $division->id,
        'order_number' => 'WO-002',
        'status' => WarehouseOrderStatus::Pending,
    ]);

    expect($this->service->canDeliver($confirmedOrder))->toBeTrue();
    expect($this->service->canDeliver($pendingOrder))->toBeFalse();
});

/**
 * Memastikan validasi fungsional (canReceive) hanya memperbolehkan penerimaan pada pesanan yang sudah dikirim (Delivered).
 */
it('checks if order can be received', function () {
    $division = Division::factory()->create();
    $user = User::factory()->create(['division_id' => $division->id]);

    $deliveredOrder = WarehouseOrder::create([
        'user_id' => $user->id,
        'division_id' => $division->id,
        'order_number' => 'WO-001',
        'status' => WarehouseOrderStatus::Delivered,
    ]);

    $pendingOrder = WarehouseOrder::create([
        'user_id' => $user->id,
        'division_id' => $division->id,
        'order_number' => 'WO-002',
        'status' => WarehouseOrderStatus::Pending,
    ]);

    expect($this->service->canReceive($deliveredOrder, $user))->toBeTrue();
    expect($this->service->canReceive($pendingOrder, $user))->toBeFalse();
});

/**
 * Memastikan validasi akses (canView) memperbolehkan user peminta atau user dengan izin global untuk melihat pesanan.
 */
it('checks view permission', function () {
    $division = Division::factory()->create();
    $user = User::factory()->create(['division_id' => $division->id]);
    $anotherUser = User::factory()->create();
    $anotherUser->givePermissionTo(InventoryPermission::ViewAllWarehouseOrder->value);

    $order = WarehouseOrder::create([
        'user_id' => $user->id,
        'division_id' => $division->id,
        'order_number' => 'WO-001',
        'status' => WarehouseOrderStatus::Pending,
    ]);

    expect($this->service->canView($order, $user))->toBeTrue();
    expect($this->service->canView($order, $anotherUser))->toBeTrue();
});
