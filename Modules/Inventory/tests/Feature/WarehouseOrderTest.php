<?php

use App\Models\Division;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Inventory\Enums\InventoryPermission;
use Modules\Inventory\Enums\ItemTransactionType;
use Modules\Inventory\Enums\WarehouseOrderStatus;
use Modules\Inventory\Models\CategoryItem;
use Modules\Inventory\Models\Item;
use Modules\Inventory\Models\ItemTransaction;
use Modules\Inventory\Models\WarehouseOrder;
use Modules\Inventory\Models\WarehouseOrderCart;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    // Create all inventory permissions
    $permissions = InventoryPermission::values();
    foreach ($permissions as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }

    // Create shared test fixtures
    $this->divisionA = Division::factory()->create(['name' => 'Divisi A']);
    $this->divisionB = Division::factory()->create(['name' => 'Divisi B']);

    $this->category = CategoryItem::create(['name' => 'ATK', 'is_active' => true]);

    $this->warehouseItem1 = Item::create([
        'category_id' => $this->category->id,
        'name' => 'Kertas HVS',
        'unit_of_measure' => 'rim',
        'stock' => 100,
    ]);

    $this->warehouseItem2 = Item::create([
        'category_id' => $this->category->id,
        'name' => 'Pulpen Hitam',
        'unit_of_measure' => 'pcs',
        'stock' => 200,
    ]);
});

// ============================================================================
// 1. Akses halaman index, datatable, print berdasarkan permission
// ============================================================================

describe('Akses Halaman (Permission: Lihat Permintaan Barang Divisi / Lihat Semua Permintaan Barang)', function () {

    it('user dengan permission ViewWarehouseOrderDivisi dapat mengakses halaman index', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::ViewWarehouseOrderDivisi->value);

        $this->actingAs($user)->get('/inventory/warehouse-orders')
            ->assertOk();
    });

    it('user dengan permission ViewAllWarehouseOrder dapat mengakses halaman index', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(InventoryPermission::ViewAllWarehouseOrder->value);

        $this->actingAs($user)->get('/inventory/warehouse-orders')
            ->assertOk();
    });

    it('user tanpa permission tidak dapat mengakses halaman index', function () {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/inventory/warehouse-orders')
            ->assertForbidden();
    });

    it('user dengan permission ViewWarehouseOrderDivisi dapat mengakses datatable', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::ViewWarehouseOrderDivisi->value);

        $this->actingAs($user)->get('/inventory/warehouse-orders/datatable')
            ->assertOk();
    });

    it('user dengan permission ViewAllWarehouseOrder dapat mengakses datatable', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(InventoryPermission::ViewAllWarehouseOrder->value);

        $this->actingAs($user)->get('/inventory/warehouse-orders/datatable')
            ->assertOk();
    });

    it('user tanpa permission tidak dapat mengakses datatable', function () {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/inventory/warehouse-orders/datatable')
            ->assertForbidden();
    });

    it('user dengan permission ViewWarehouseOrderDivisi dapat mengakses print-excel', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::ViewWarehouseOrderDivisi->value);

        $this->actingAs($user)->get('/inventory/warehouse-orders/print-excel')
            ->assertOk();
    });

    it('user dengan permission ViewAllWarehouseOrder dapat mengakses print-excel', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(InventoryPermission::ViewAllWarehouseOrder->value);

        $this->actingAs($user)->get('/inventory/warehouse-orders/print-excel')
            ->assertOk();
    });

    it('user tanpa permission tidak dapat mengakses print-excel', function () {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/inventory/warehouse-orders/print-excel')
            ->assertForbidden();
    });
});

// ============================================================================
// 2. Datatable filter berdasarkan division_id untuk ViewWarehouseOrderDivisi
// ============================================================================

describe('Datatable Filter Division (ViewWarehouseOrderDivisi)', function () {

    it('user dengan ViewWarehouseOrderDivisi hanya melihat data divisi sendiri', function () {
        $userA = User::factory()->create(['division_id' => $this->divisionA->id]);
        $userA->givePermissionTo(InventoryPermission::ViewWarehouseOrderDivisi->value);

        $userB = User::factory()->create(['division_id' => $this->divisionB->id]);

        // Order divisi A
        WarehouseOrder::create([
            'user_id' => $userA->id,
            'division_id' => $this->divisionA->id,
            'order_number' => 'WO-DIV-A-001',
            'status' => WarehouseOrderStatus::Pending,
        ]);

        // Order divisi B
        WarehouseOrder::create([
            'user_id' => $userB->id,
            'division_id' => $this->divisionB->id,
            'order_number' => 'WO-DIV-B-001',
            'status' => WarehouseOrderStatus::Pending,
        ]);

        $response = $this->actingAs($userA)->get('/inventory/warehouse-orders/datatable');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.order_number', 'WO-DIV-A-001');
    });
});

// ============================================================================
// 3. Datatable ViewAllWarehouseOrder melihat semua data
// ============================================================================

describe('Datatable Filter Division (ViewAllWarehouseOrder)', function () {

    it('user dengan ViewAllWarehouseOrder melihat semua data tanpa filter divisi', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(InventoryPermission::ViewAllWarehouseOrder->value);

        $userA = User::factory()->create(['division_id' => $this->divisionA->id]);
        $userB = User::factory()->create(['division_id' => $this->divisionB->id]);

        WarehouseOrder::create([
            'user_id' => $userA->id,
            'division_id' => $this->divisionA->id,
            'order_number' => 'WO-ALL-001',
            'status' => WarehouseOrderStatus::Pending,
        ]);

        WarehouseOrder::create([
            'user_id' => $userB->id,
            'division_id' => $this->divisionB->id,
            'order_number' => 'WO-ALL-002',
            'status' => WarehouseOrderStatus::Confirmed,
        ]);

        $response = $this->actingAs($user)->get('/inventory/warehouse-orders/datatable');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    });
});

// ============================================================================
// 4. CRUD: Create, Store, Edit, Update, Delete (permission: Buat Permintaan Barang)
// ============================================================================

describe('CRUD Permintaan Barang (Permission: Buat Permintaan Barang)', function () {

    it('user dengan CreateWarehouseOrder dapat mengakses halaman create', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::CreateWarehouseOrder->value);

        $this->actingAs($user)->get('/inventory/warehouse-orders/create')
            ->assertOk();
    });

    it('user tanpa CreateWarehouseOrder tidak dapat mengakses halaman create', function () {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/inventory/warehouse-orders/create')
            ->assertForbidden();
    });

    it('store: status harus Pending dan user_id harus sesuai user login', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::CreateWarehouseOrder->value);

        $response = $this->actingAs($user)->post('/inventory/warehouse-orders/store', [
            'description' => 'Permintaan ATK bulan ini',
            'items' => [
                ['item_id' => $this->warehouseItem1->id, 'quantity' => 5],
                ['item_id' => $this->warehouseItem2->id, 'quantity' => 10],
            ],
        ]);

        $response->assertRedirect('/inventory/warehouse-orders');

        $this->assertDatabaseHas('warehouse_orders', [
            'user_id' => $user->id,
            'division_id' => $this->divisionA->id,
            'status' => WarehouseOrderStatus::Pending->value,
        ]);

        // Pastikan carts dibuat
        $order = WarehouseOrder::where('user_id', $user->id)->first();
        expect($order->carts)->toHaveCount(2);
    });

    it('store: validasi field items wajib diisi', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::CreateWarehouseOrder->value);

        $response = $this->actingAs($user)->post('/inventory/warehouse-orders/store', []);
        $response->assertSessionHasErrors(['items']);
    });

    it('user tanpa CreateWarehouseOrder tidak bisa store', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);

        $response = $this->actingAs($user)->post('/inventory/warehouse-orders/store', [
            'description' => 'Test',
            'items' => [
                ['item_id' => $this->warehouseItem1->id, 'quantity' => 5],
            ],
        ]);

        $response->assertForbidden();
    });

    it('edit: user dengan CreateWarehouseOrder dapat mengedit order Pending', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::CreateWarehouseOrder->value);

        $order = WarehouseOrder::create([
            'user_id' => $user->id,
            'division_id' => $this->divisionA->id,
            'order_number' => 'WO-EDIT-001',
            'status' => WarehouseOrderStatus::Pending,
        ]);

        WarehouseOrderCart::create([
            'warehouse_order_id' => $order->id,
            'item_id' => $this->warehouseItem1->id,
            'quantity' => 5,
        ]);

        $this->actingAs($user)->get("/inventory/warehouse-orders/{$order->id}/edit")
            ->assertOk();
    });

    it('edit: tidak bisa mengedit order yang sudah Confirmed', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::CreateWarehouseOrder->value);

        $order = WarehouseOrder::create([
            'user_id' => $user->id,
            'division_id' => $this->divisionA->id,
            'order_number' => 'WO-EDIT-002',
            'status' => WarehouseOrderStatus::Confirmed,
        ]);

        $this->actingAs($user)->get("/inventory/warehouse-orders/{$order->id}/edit")
            ->assertForbidden();
    });

    it('update: dapat update order Pending dan status tetap Pending', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::CreateWarehouseOrder->value);

        $order = WarehouseOrder::create([
            'user_id' => $user->id,
            'division_id' => $this->divisionA->id,
            'order_number' => 'WO-UPD-001',
            'status' => WarehouseOrderStatus::Pending,
        ]);

        WarehouseOrderCart::create([
            'warehouse_order_id' => $order->id,
            'item_id' => $this->warehouseItem1->id,
            'quantity' => 5,
        ]);

        $response = $this->actingAs($user)->put("/inventory/warehouse-orders/{$order->id}/update", [
            'description' => 'Updated description',
            'items' => [
                ['item_id' => $this->warehouseItem2->id, 'quantity' => 15],
            ],
        ]);

        $response->assertRedirect('/inventory/warehouse-orders');

        $this->assertDatabaseHas('warehouse_orders', [
            'id' => $order->id,
            'status' => WarehouseOrderStatus::Pending->value,
            'description' => 'Updated description',
        ]);
    });

    it('update: tidak bisa update order Confirmed', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::CreateWarehouseOrder->value);

        $order = WarehouseOrder::create([
            'user_id' => $user->id,
            'division_id' => $this->divisionA->id,
            'order_number' => 'WO-UPD-002',
            'status' => WarehouseOrderStatus::Confirmed,
        ]);

        $response = $this->actingAs($user)->put("/inventory/warehouse-orders/{$order->id}/update", [
            'description' => 'Test',
            'items' => [
                ['item_id' => $this->warehouseItem1->id, 'quantity' => 5],
            ],
        ]);

        $response->assertForbidden();
    });

    it('delete: dapat menghapus order Pending', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::CreateWarehouseOrder->value);

        $order = WarehouseOrder::create([
            'user_id' => $user->id,
            'division_id' => $this->divisionA->id,
            'order_number' => 'WO-DEL-001',
            'status' => WarehouseOrderStatus::Pending,
        ]);

        WarehouseOrderCart::create([
            'warehouse_order_id' => $order->id,
            'item_id' => $this->warehouseItem1->id,
            'quantity' => 5,
        ]);

        $response = $this->actingAs($user)->delete("/inventory/warehouse-orders/{$order->id}/delete");

        $response->assertRedirect('/inventory/warehouse-orders');
        $this->assertDatabaseMissing('warehouse_orders', ['id' => $order->id]);
    });

    it('delete: tidak bisa menghapus order Confirmed', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::CreateWarehouseOrder->value);

        $order = WarehouseOrder::create([
            'user_id' => $user->id,
            'division_id' => $this->divisionA->id,
            'order_number' => 'WO-DEL-002',
            'status' => WarehouseOrderStatus::Confirmed,
        ]);

        $response = $this->actingAs($user)->delete("/inventory/warehouse-orders/{$order->id}/delete");

        $response->assertForbidden();
        $this->assertDatabaseHas('warehouse_orders', ['id' => $order->id]);
    });
});

// ============================================================================
// 5. Confirm & Reject (Permission: Konfirmasi Permintaan Barang)
// ============================================================================

describe('Konfirmasi & Tolak Permintaan (Permission: Konfirmasi Permintaan Barang)', function () {

    it('confirm: user dengan ConfirmWarehouseOrder dapat mengkonfirmasi order Pending', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(InventoryPermission::ConfirmWarehouseOrder->value);

        $requester = User::factory()->create(['division_id' => $this->divisionA->id]);
        $order = WarehouseOrder::create([
            'user_id' => $requester->id,
            'division_id' => $this->divisionA->id,
            'order_number' => 'WO-CONF-001',
            'status' => WarehouseOrderStatus::Pending,
        ]);

        $response = $this->actingAs($user)->patch("/inventory/warehouse-orders/{$order->id}/confirm");

        $response->assertRedirect();
        $this->assertDatabaseHas('warehouse_orders', [
            'id' => $order->id,
            'status' => WarehouseOrderStatus::Confirmed->value,
        ]);
    });

    it('confirm: user tanpa ConfirmWarehouseOrder tidak bisa mengkonfirmasi', function () {
        $user = User::factory()->create();

        $requester = User::factory()->create(['division_id' => $this->divisionA->id]);
        $order = WarehouseOrder::create([
            'user_id' => $requester->id,
            'division_id' => $this->divisionA->id,
            'order_number' => 'WO-CONF-002',
            'status' => WarehouseOrderStatus::Pending,
        ]);

        $response = $this->actingAs($user)->patch("/inventory/warehouse-orders/{$order->id}/confirm");

        $response->assertForbidden();
    });

    it('reject: user dengan ConfirmWarehouseOrder dapat menolak order Pending', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(InventoryPermission::ConfirmWarehouseOrder->value);

        $requester = User::factory()->create(['division_id' => $this->divisionA->id]);
        $order = WarehouseOrder::create([
            'user_id' => $requester->id,
            'division_id' => $this->divisionA->id,
            'order_number' => 'WO-REJ-001',
            'status' => WarehouseOrderStatus::Pending,
        ]);

        WarehouseOrderCart::create([
            'warehouse_order_id' => $order->id,
            'item_id' => $this->warehouseItem1->id,
            'quantity' => 5,
        ]);

        $response = $this->actingAs($user)->post("/inventory/warehouse-orders/{$order->id}/reject", [
            'reason' => 'Stok tidak tersedia',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('warehouse_orders', [
            'id' => $order->id,
            'status' => WarehouseOrderStatus::Rejected->value,
        ]);
    });

    it('reject: user tanpa ConfirmWarehouseOrder tidak bisa menolak', function () {
        $user = User::factory()->create();

        $requester = User::factory()->create(['division_id' => $this->divisionA->id]);
        $order = WarehouseOrder::create([
            'user_id' => $requester->id,
            'division_id' => $this->divisionA->id,
            'order_number' => 'WO-REJ-002',
            'status' => WarehouseOrderStatus::Pending,
        ]);

        $response = $this->actingAs($user)->post("/inventory/warehouse-orders/{$order->id}/reject", [
            'reason' => 'Stok tidak tersedia',
        ]);

        $response->assertForbidden();
    });

    it('confirm: dapat mengkonfirmasi order Revision', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(InventoryPermission::ConfirmWarehouseOrder->value);

        $requester = User::factory()->create(['division_id' => $this->divisionA->id]);
        $order = WarehouseOrder::create([
            'user_id' => $requester->id,
            'division_id' => $this->divisionA->id,
            'order_number' => 'WO-CONF-REV-001',
            'status' => WarehouseOrderStatus::Revision,
        ]);

        $response = $this->actingAs($user)->patch("/inventory/warehouse-orders/{$order->id}/confirm");

        $response->assertRedirect();
        $this->assertDatabaseHas('warehouse_orders', [
            'id' => $order->id,
            'status' => WarehouseOrderStatus::Confirmed->value,
        ]);
    });
});

// ============================================================================
// 6. Delivery (Permission: Serah Terima Barang)
// ============================================================================

describe('Serah Terima Barang (Permission: HandoverItem)', function () {

    it('user dengan HandoverItem dapat mengakses halaman delivery', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(InventoryPermission::HandoverItem->value);

        $requester = User::factory()->create(['division_id' => $this->divisionA->id]);
        $order = WarehouseOrder::create([
            'user_id' => $requester->id,
            'division_id' => $this->divisionA->id,
            'order_number' => 'WO-DEL-VIEW-001',
            'status' => WarehouseOrderStatus::Confirmed,
        ]);

        WarehouseOrderCart::create([
            'warehouse_order_id' => $order->id,
            'item_id' => $this->warehouseItem1->id,
            'quantity' => 5,
        ]);

        $this->actingAs($user)->get("/inventory/warehouse-orders/{$order->id}/delivery")
            ->assertOk();
    });

    it('user tanpa HandoverItem tidak dapat mengakses delivery', function () {
        $user = User::factory()->create();

        $requester = User::factory()->create(['division_id' => $this->divisionA->id]);
        $order = WarehouseOrder::create([
            'user_id' => $requester->id,
            'division_id' => $this->divisionA->id,
            'order_number' => 'WO-DEL-DENY-001',
            'status' => WarehouseOrderStatus::Confirmed,
        ]);

        $this->actingAs($user)->get("/inventory/warehouse-orders/{$order->id}/delivery")
            ->assertForbidden();
    });

    it('delivery: status berubah menjadi Delivered', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(InventoryPermission::HandoverItem->value);

        $requester = User::factory()->create(['division_id' => $this->divisionA->id]);
        $order = WarehouseOrder::create([
            'user_id' => $requester->id,
            'division_id' => $this->divisionA->id,
            'order_number' => 'WO-DELIVER-001',
            'status' => WarehouseOrderStatus::Confirmed,
        ]);

        $cart = WarehouseOrderCart::create([
            'warehouse_order_id' => $order->id,
            'item_id' => $this->warehouseItem1->id,
            'quantity' => 5,
        ]);

        $response = $this->actingAs($user)->post("/inventory/warehouse-orders/{$order->id}/delivery", [
            'delivery_date' => now()->format('Y-m-d'),
            'delivery_images' => [
                UploadedFile::fake()->image('delivery.jpg'),
            ],
        ]);

        $response->assertRedirect('/inventory/warehouse-orders');
        $this->assertDatabaseHas('warehouse_orders', [
            'id' => $order->id,
            'status' => WarehouseOrderStatus::Delivered->value,
        ]);
    });
});

// ============================================================================
// 7. Receive (Permission: Terima Barang)
// ============================================================================

describe('Terima Barang (Permission: ReceiveItem)', function () {

    it('user dengan ReceiveItem dapat mengakses halaman receive', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::ReceiveItem->value);

        $order = WarehouseOrder::create([
            'user_id' => $user->id,
            'division_id' => $this->divisionA->id,
            'order_number' => 'WO-RCV-VIEW-001',
            'status' => WarehouseOrderStatus::Delivered,
            'delivered_by' => $user->id,
            'delivery_date' => now(),
        ]);

        WarehouseOrderCart::create([
            'warehouse_order_id' => $order->id,
            'item_id' => $this->warehouseItem1->id,
            'quantity' => 5,
            'delivered_quantity' => 5,
        ]);

        $this->actingAs($user)->get("/inventory/warehouse-orders/{$order->id}/receive")
            ->assertOk();
    });

    it('user tanpa ReceiveItem tidak dapat mengakses receive', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);

        $order = WarehouseOrder::create([
            'user_id' => $user->id,
            'division_id' => $this->divisionA->id,
            'order_number' => 'WO-RCV-DENY-001',
            'status' => WarehouseOrderStatus::Delivered,
            'delivered_by' => $user->id,
            'delivery_date' => now(),
        ]);

        $this->actingAs($user)->get("/inventory/warehouse-orders/{$order->id}/receive")
            ->assertForbidden();
    });

    it('receive: status berubah menjadi Finished', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::ReceiveItem->value);

        $order = WarehouseOrder::create([
            'user_id' => $user->id,
            'division_id' => $this->divisionA->id,
            'order_number' => 'WO-RECEIVE-001',
            'status' => WarehouseOrderStatus::Delivered,
            'delivered_by' => $user->id,
            'delivery_date' => now(),
        ]);

        WarehouseOrderCart::create([
            'warehouse_order_id' => $order->id,
            'item_id' => $this->warehouseItem1->id,
            'quantity' => 5,
            'delivered_quantity' => 5,
        ]);

        $response = $this->actingAs($user)->post("/inventory/warehouse-orders/{$order->id}/receive", [
            'receipt_date' => now()->format('Y-m-d'),
            'receipt_images' => [
                UploadedFile::fake()->image('receipt.jpg'),
            ],
        ]);

        $response->assertRedirect('/inventory/warehouse-orders');
        $this->assertDatabaseHas('warehouse_orders', [
            'id' => $order->id,
            'status' => WarehouseOrderStatus::Finished->value,
        ]);
    });
});

// ============================================================================
// 8. Delivery: item_transaction tercatat sebagai Barang Keluar
// ============================================================================

describe('Delivery: Pencatatan Item Transaction (Barang Keluar)', function () {

    it('saat delivery, item_transaction tercatat sebagai Out dengan item_id dan quantity yang benar', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(InventoryPermission::HandoverItem->value);

        $requester = User::factory()->create(['division_id' => $this->divisionA->id]);
        $order = WarehouseOrder::create([
            'user_id' => $requester->id,
            'division_id' => $this->divisionA->id,
            'order_number' => 'WO-TXN-OUT-001',
            'status' => WarehouseOrderStatus::Confirmed,
        ]);

        WarehouseOrderCart::create([
            'warehouse_order_id' => $order->id,
            'item_id' => $this->warehouseItem1->id,
            'quantity' => 10,
        ]);

        WarehouseOrderCart::create([
            'warehouse_order_id' => $order->id,
            'item_id' => $this->warehouseItem2->id,
            'quantity' => 20,
        ]);

        $deliveryDate = now()->format('Y-m-d');

        $this->actingAs($user)->post("/inventory/warehouse-orders/{$order->id}/delivery", [
            'delivery_date' => $deliveryDate,
            'delivery_images' => [
                UploadedFile::fake()->image('delivery.jpg'),
            ],
        ]);

        // Pastikan item_transaction untuk warehouseItem1
        $this->assertDatabaseHas('item_transactions', [
            'item_id' => $this->warehouseItem1->id,
            'type' => ItemTransactionType::Out->value,
            'quantity' => 10,
            'user_id' => $user->id,
        ]);

        // Pastikan item_transaction untuk warehouseItem2
        $this->assertDatabaseHas('item_transactions', [
            'item_id' => $this->warehouseItem2->id,
            'type' => ItemTransactionType::Out->value,
            'quantity' => 20,
            'user_id' => $user->id,
        ]);

        // Pastikan total transaksi yang dibuat = 2
        expect(ItemTransaction::where('type', ItemTransactionType::Out->value)->count())->toBe(2);
    });
});

// ============================================================================
// 9. Receive: item_transaction tercatat sebagai Barang Masuk
// ============================================================================

describe('Receive: Pencatatan Item Transaction (Barang Masuk)', function () {

    it('saat receive, item_transaction tercatat sebagai In dengan item_id dan quantity yang benar', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::ReceiveItem->value);

        $order = WarehouseOrder::create([
            'user_id' => $user->id,
            'division_id' => $this->divisionA->id,
            'order_number' => 'WO-TXN-IN-001',
            'status' => WarehouseOrderStatus::Delivered,
            'delivered_by' => $user->id,
            'delivery_date' => now(),
        ]);

        WarehouseOrderCart::create([
            'warehouse_order_id' => $order->id,
            'item_id' => $this->warehouseItem1->id,
            'quantity' => 8,
            'delivered_quantity' => 8,
        ]);

        WarehouseOrderCart::create([
            'warehouse_order_id' => $order->id,
            'item_id' => $this->warehouseItem2->id,
            'quantity' => 15,
            'delivered_quantity' => 15,
        ]);

        $receiptDate = now()->format('Y-m-d');

        $this->actingAs($user)->post("/inventory/warehouse-orders/{$order->id}/receive", [
            'receipt_date' => $receiptDate,
            'receipt_images' => [
                UploadedFile::fake()->image('receipt.jpg'),
            ],
        ]);

        // Pastikan item_transaction untuk warehouseItem1 sebagai In (di division item)
        $divisionItem1 = Item::where('division_id', $this->divisionA->id)
            ->where('main_reference_item_id', $this->warehouseItem1->id)
            ->first();

        expect($divisionItem1)->not->toBeNull();

        $this->assertDatabaseHas('item_transactions', [
            'item_id' => $divisionItem1->id,
            'type' => ItemTransactionType::In->value,
            'quantity' => 8,
            'user_id' => $user->id,
        ]);

        // Pastikan item_transaction untuk warehouseItem2 sebagai In (di division item)
        $divisionItem2 = Item::where('division_id', $this->divisionA->id)
            ->where('main_reference_item_id', $this->warehouseItem2->id)
            ->first();

        expect($divisionItem2)->not->toBeNull();

        $this->assertDatabaseHas('item_transactions', [
            'item_id' => $divisionItem2->id,
            'type' => ItemTransactionType::In->value,
            'quantity' => 15,
            'user_id' => $user->id,
        ]);

        // Pastikan total transaksi In = 2
        expect(ItemTransaction::where('type', ItemTransactionType::In->value)->count())->toBe(2);
    });

    it('saat receive, stok division item bertambah sesuai quantity', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::ReceiveItem->value);

        $order = WarehouseOrder::create([
            'user_id' => $user->id,
            'division_id' => $this->divisionA->id,
            'order_number' => 'WO-TXN-IN-002',
            'status' => WarehouseOrderStatus::Delivered,
            'delivered_by' => $user->id,
            'delivery_date' => now(),
        ]);

        WarehouseOrderCart::create([
            'warehouse_order_id' => $order->id,
            'item_id' => $this->warehouseItem1->id,
            'quantity' => 12,
            'delivered_quantity' => 12,
        ]);

        $this->actingAs($user)->post("/inventory/warehouse-orders/{$order->id}/receive", [
            'receipt_date' => now()->format('Y-m-d'),
            'receipt_images' => [
                UploadedFile::fake()->image('receipt.jpg'),
            ],
        ]);

        $divisionItem = Item::where('division_id', $this->divisionA->id)
            ->where('main_reference_item_id', $this->warehouseItem1->id)
            ->first();

        expect($divisionItem)->not->toBeNull();
        expect($divisionItem->stock)->toBe(12);
    });
});

// ============================================================================
// 10. Datatable: Pagination, Limit, dan Global Search
// ============================================================================

describe('Datatable: Pagination, Limit, dan Search', function () {

    it('pagination bekerja dengan benar', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(InventoryPermission::ViewAllWarehouseOrder->value);

        $requester = User::factory()->create(['division_id' => $this->divisionA->id]);

        // Buat 25 order
        for ($i = 1; $i <= 25; $i++) {
            WarehouseOrder::create([
                'user_id' => $requester->id,
                'division_id' => $this->divisionA->id,
                'order_number' => 'WO-PAGE-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'status' => WarehouseOrderStatus::Pending,
            ]);
        }

        // Halaman pertama (default limit 20)
        $response = $this->actingAs($user)->get('/inventory/warehouse-orders/datatable');
        $response->assertOk();
        $response->assertJsonCount(20, 'data');
        $response->assertJsonPath('last_page', 2);
        $response->assertJsonPath('total', 25);

        // Halaman kedua
        $response2 = $this->actingAs($user)->get('/inventory/warehouse-orders/datatable?page=2');
        $response2->assertOk();
        $response2->assertJsonCount(5, 'data');
    });

    it('limit bekerja dengan benar', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(InventoryPermission::ViewAllWarehouseOrder->value);

        $requester = User::factory()->create(['division_id' => $this->divisionA->id]);

        for ($i = 1; $i <= 15; $i++) {
            WarehouseOrder::create([
                'user_id' => $requester->id,
                'division_id' => $this->divisionA->id,
                'order_number' => 'WO-LIM-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'status' => WarehouseOrderStatus::Pending,
            ]);
        }

        // Set limit 5
        $response = $this->actingAs($user)->get('/inventory/warehouse-orders/datatable?limit=5');
        $response->assertOk();
        $response->assertJsonCount(5, 'data');
        $response->assertJsonPath('last_page', 3);
        $response->assertJsonPath('total', 15);

        // Set limit 10
        $response2 = $this->actingAs($user)->get('/inventory/warehouse-orders/datatable?limit=10');
        $response2->assertOk();
        $response2->assertJsonCount(10, 'data');
        $response2->assertJsonPath('last_page', 2);
    });

    it('search global berfungsi mencari berdasarkan order_number', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(InventoryPermission::ViewAllWarehouseOrder->value);

        $requester = User::factory()->create(['division_id' => $this->divisionA->id]);

        WarehouseOrder::create([
            'user_id' => $requester->id,
            'division_id' => $this->divisionA->id,
            'order_number' => 'WO-SEARCH-ABC',
            'description' => 'Permintaan pertama',
            'status' => WarehouseOrderStatus::Pending,
        ]);

        WarehouseOrder::create([
            'user_id' => $requester->id,
            'division_id' => $this->divisionA->id,
            'order_number' => 'WO-SEARCH-XYZ',
            'description' => 'Permintaan kedua',
            'status' => WarehouseOrderStatus::Pending,
        ]);

        $response = $this->actingAs($user)->get('/inventory/warehouse-orders/datatable?search=ABC');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.order_number', 'WO-SEARCH-ABC');
    });

    it('search global berfungsi mencari berdasarkan description', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(InventoryPermission::ViewAllWarehouseOrder->value);

        $requester = User::factory()->create(['division_id' => $this->divisionA->id]);

        WarehouseOrder::create([
            'user_id' => $requester->id,
            'division_id' => $this->divisionA->id,
            'order_number' => 'WO-DESC-001',
            'description' => 'Permintaan ATK bulanan',
            'status' => WarehouseOrderStatus::Pending,
        ]);

        WarehouseOrder::create([
            'user_id' => $requester->id,
            'division_id' => $this->divisionA->id,
            'order_number' => 'WO-DESC-002',
            'description' => 'Permintaan furnitur',
            'status' => WarehouseOrderStatus::Pending,
        ]);

        $response = $this->actingAs($user)->get('/inventory/warehouse-orders/datatable?search=ATK');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.order_number', 'WO-DESC-001');
    });
});

// ============================================================================
// 11. Individual Column Search/Filter di Footer
// ============================================================================

describe('Datatable: Individual Column Filter (Footer Search)', function () {

    it('filter berdasarkan kolom order_number', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(InventoryPermission::ViewAllWarehouseOrder->value);

        $requester = User::factory()->create(['division_id' => $this->divisionA->id]);

        WarehouseOrder::create([
            'user_id' => $requester->id,
            'division_id' => $this->divisionA->id,
            'order_number' => 'WO-COL-ALPHA',
            'status' => WarehouseOrderStatus::Pending,
        ]);

        WarehouseOrder::create([
            'user_id' => $requester->id,
            'division_id' => $this->divisionA->id,
            'order_number' => 'WO-COL-BETA',
            'status' => WarehouseOrderStatus::Pending,
        ]);

        $response = $this->actingAs($user)->get('/inventory/warehouse-orders/datatable?order_number=ALPHA');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.order_number', 'WO-COL-ALPHA');
    });

    it('filter berdasarkan kolom status', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(InventoryPermission::ViewAllWarehouseOrder->value);

        $requester = User::factory()->create(['division_id' => $this->divisionA->id]);

        WarehouseOrder::create([
            'user_id' => $requester->id,
            'division_id' => $this->divisionA->id,
            'order_number' => 'WO-STAT-001',
            'status' => WarehouseOrderStatus::Pending,
        ]);

        WarehouseOrder::create([
            'user_id' => $requester->id,
            'division_id' => $this->divisionA->id,
            'order_number' => 'WO-STAT-002',
            'status' => WarehouseOrderStatus::Confirmed,
        ]);

        WarehouseOrder::create([
            'user_id' => $requester->id,
            'division_id' => $this->divisionA->id,
            'order_number' => 'WO-STAT-003',
            'status' => WarehouseOrderStatus::Rejected,
        ]);

        $response = $this->actingAs($user)->get('/inventory/warehouse-orders/datatable?status=Pending');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.order_number', 'WO-STAT-001');

        $response2 = $this->actingAs($user)->get('/inventory/warehouse-orders/datatable?status=Confirmed');
        $response2->assertOk();
        $response2->assertJsonCount(1, 'data');
        $response2->assertJsonPath('data.0.order_number', 'WO-STAT-002');
    });

    it('filter berdasarkan kolom division_id', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(InventoryPermission::ViewAllWarehouseOrder->value);

        $requesterA = User::factory()->create(['division_id' => $this->divisionA->id]);
        $requesterB = User::factory()->create(['division_id' => $this->divisionB->id]);

        WarehouseOrder::create([
            'user_id' => $requesterA->id,
            'division_id' => $this->divisionA->id,
            'order_number' => 'WO-DIVF-A',
            'status' => WarehouseOrderStatus::Pending,
        ]);

        WarehouseOrder::create([
            'user_id' => $requesterB->id,
            'division_id' => $this->divisionB->id,
            'order_number' => 'WO-DIVF-B',
            'status' => WarehouseOrderStatus::Pending,
        ]);

        $response = $this->actingAs($user)->get("/inventory/warehouse-orders/datatable?division_id={$this->divisionA->id}");
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.order_number', 'WO-DIVF-A');
    });

    it('filter berdasarkan kolom user_id (nama user)', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(InventoryPermission::ViewAllWarehouseOrder->value);

        $requester1 = User::factory()->create(['name' => 'Budi Santoso', 'division_id' => $this->divisionA->id]);
        $requester2 = User::factory()->create(['name' => 'Andi Wirawan', 'division_id' => $this->divisionB->id]);

        WarehouseOrder::create([
            'user_id' => $requester1->id,
            'division_id' => $this->divisionA->id,
            'order_number' => 'WO-USER-BUDI',
            'status' => WarehouseOrderStatus::Pending,
        ]);

        WarehouseOrder::create([
            'user_id' => $requester2->id,
            'division_id' => $this->divisionB->id,
            'order_number' => 'WO-USER-ANDI',
            'status' => WarehouseOrderStatus::Pending,
        ]);

        $response = $this->actingAs($user)->get('/inventory/warehouse-orders/datatable?user_id=Budi');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.order_number', 'WO-USER-BUDI');
    });

    it('filter berdasarkan kolom created_at (bulan/tahun)', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(InventoryPermission::ViewAllWarehouseOrder->value);

        $requester = User::factory()->create(['division_id' => $this->divisionA->id]);

        $orderJan = WarehouseOrder::create([
            'user_id' => $requester->id,
            'division_id' => $this->divisionA->id,
            'order_number' => 'WO-DATE-JAN',
            'status' => WarehouseOrderStatus::Pending,
        ]);
        $orderJan->forceFill(['created_at' => '2026-01-15 10:00:00'])->save();

        $orderFeb = WarehouseOrder::create([
            'user_id' => $requester->id,
            'division_id' => $this->divisionA->id,
            'order_number' => 'WO-DATE-FEB',
            'status' => WarehouseOrderStatus::Pending,
        ]);
        $orderFeb->forceFill(['created_at' => '2026-02-10 10:00:00'])->save();

        $response = $this->actingAs($user)->get('/inventory/warehouse-orders/datatable?created_at=2026-01');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.order_number', 'WO-DATE-JAN');
    });

    it('filter status ALL mengambil semua data', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(InventoryPermission::ViewAllWarehouseOrder->value);

        $requester = User::factory()->create(['division_id' => $this->divisionA->id]);

        WarehouseOrder::create([
            'user_id' => $requester->id,
            'division_id' => $this->divisionA->id,
            'order_number' => 'WO-ALL-S1',
            'status' => WarehouseOrderStatus::Pending,
        ]);

        WarehouseOrder::create([
            'user_id' => $requester->id,
            'division_id' => $this->divisionA->id,
            'order_number' => 'WO-ALL-S2',
            'status' => WarehouseOrderStatus::Confirmed,
        ]);

        $response = $this->actingAs($user)->get('/inventory/warehouse-orders/datatable?status=ALL');
        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    });

    it('filter division_id ALL mengambil semua data', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(InventoryPermission::ViewAllWarehouseOrder->value);

        $requesterA = User::factory()->create(['division_id' => $this->divisionA->id]);
        $requesterB = User::factory()->create(['division_id' => $this->divisionB->id]);

        WarehouseOrder::create([
            'user_id' => $requesterA->id,
            'division_id' => $this->divisionA->id,
            'order_number' => 'WO-ALLD-A',
            'status' => WarehouseOrderStatus::Pending,
        ]);

        WarehouseOrder::create([
            'user_id' => $requesterB->id,
            'division_id' => $this->divisionB->id,
            'order_number' => 'WO-ALLD-B',
            'status' => WarehouseOrderStatus::Pending,
        ]);

        $response = $this->actingAs($user)->get('/inventory/warehouse-orders/datatable?division_id=ALL');
        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    });
});
