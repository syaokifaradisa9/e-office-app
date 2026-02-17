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

describe('Page Access (Permission: ViewWarehouseOrderDivisi / ViewAllWarehouseOrder)', function () {

    /**
     * Memastikan user dengan izin 'lihat_permintaan_barang_divisi' dapat mengakses halaman index.
     */
    it('allows user with ViewWarehouseOrderDivisi permission to access index page', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::ViewWarehouseOrderDivisi->value);

        $this->actingAs($user)->get('/inventory/warehouse-orders')
            ->assertOk();
    });

    /**
     * Memastikan user dengan izin 'lihat_semua_permintaan_barang' dapat mengakses halaman index.
     */
    it('allows user with ViewAllWarehouseOrder permission to access index page', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(InventoryPermission::ViewAllWarehouseOrder->value);

        $this->actingAs($user)->get('/inventory/warehouse-orders')
            ->assertOk();
    });

    /**
     * Memastikan user tanpa izin apapun ditolak saat mengakses halaman index.
     */
    it('denies user without permission from accessing index page', function () {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/inventory/warehouse-orders')
            ->assertForbidden();
    });

    /**
     * Memastikan user dengan izin 'lihat_permintaan_barang_divisi' dapat mengakses API datatable.
     */
    it('allows user with ViewWarehouseOrderDivisi permission to access datatable', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::ViewWarehouseOrderDivisi->value);

        $this->actingAs($user)->get('/inventory/warehouse-orders/datatable')
            ->assertOk();
    });

    /**
     * Memastikan user dengan izin 'lihat_semua_permintaan_barang' dapat mengakses API datatable.
     */
    it('allows user with ViewAllWarehouseOrder permission to access datatable', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(InventoryPermission::ViewAllWarehouseOrder->value);

        $this->actingAs($user)->get('/inventory/warehouse-orders/datatable')
            ->assertOk();
    });

    /**
     * Memastikan user tanpa izin ditolak saat mengakses API datatable.
     */
    it('denies user without permission from accessing datatable', function () {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/inventory/warehouse-orders/datatable')
            ->assertForbidden();
    });

    /**
     * Memastikan user dengan izin 'lihat_permintaan_barang_divisi' dapat mengunduh laporan excel.
     */
    it('allows user with ViewWarehouseOrderDivisi permission to access print-excel', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::ViewWarehouseOrderDivisi->value);

        $this->actingAs($user)->get('/inventory/warehouse-orders/print-excel')
            ->assertOk();
    });

    /**
     * Memastikan user dengan izin 'lihat_semua_permintaan_barang' dapat mengunduh laporan excel.
     */
    it('allows user with ViewAllWarehouseOrder permission to access print-excel', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(InventoryPermission::ViewAllWarehouseOrder->value);

        $this->actingAs($user)->get('/inventory/warehouse-orders/print-excel')
            ->assertOk();
    });

    /**
     * Memastikan user tanpa izin ditolak saat mencoba mengunduh laporan excel.
     */
    it('denies user without permission from accessing print-excel', function () {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/inventory/warehouse-orders/print-excel')
            ->assertForbidden();
    });
});

// ============================================================================
// 2. Datatable filter berdasarkan division_id untuk ViewWarehouseOrderDivisi
// ============================================================================

describe('Datatable Filter Division (ViewWarehouseOrderDivisi)', function () {

    /**
     * Memastikan scoping data: user divisi hanya boleh melihat permintaan dari divisinya sendiri.
     */
    it('only shows own division data for users with ViewWarehouseOrderDivisi', function () {
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

    /**
     * Memastikan user dengan izin global dapat melihat seluruh data permintaan tanpa filter divisi.
     */
    it('shows all data without division filter for users with ViewAllWarehouseOrder', function () {
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

describe('Warehouse Order CRUD (Permission: CreateWarehouseOrder)', function () {

    /**
     * Memastikan user dengan izin 'buat_permintaan_barang' dapat mengakses form tambah.
     */
    it('allows user with CreateWarehouseOrder permission to access create page', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::CreateWarehouseOrder->value);

        $this->actingAs($user)->get('/inventory/warehouse-orders/create')
            ->assertOk();
    });

    /**
     * Memastikan user tanpa izin 'buat_permintaan_barang' ditolak saat mengakses form tambah.
     */
    it('denies user without CreateWarehouseOrder from accessing create page', function () {
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

    /**
     * Memastikan sistem menolak penyimpanan jika field 'items' (daftar barang) kosong.
     */
    it('fails to store if items field is empty', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::CreateWarehouseOrder->value);

        $response = $this->actingAs($user)->post('/inventory/warehouse-orders/store', []);
        $response->assertSessionHasErrors(['items']);
    });

    /**
     * Memastikan user tanpa izin 'buat_permintaan_barang' gagal saat mencoba menyimpan data.
     */
    it('denies store action for users without CreateWarehouseOrder', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);

        $response = $this->actingAs($user)->post('/inventory/warehouse-orders/store', [
            'description' => 'Test',
            'items' => [
                ['item_id' => $this->warehouseItem1->id, 'quantity' => 5],
            ],
        ]);

        $response->assertForbidden();
    });

    /**
     * Memastikan user dengan izin yang sesuai dapat mengedit permintaan yang masih berstatus 'Pending'.
     */
    it('allows users with CreateWarehouseOrder to edit Pending orders', function () {
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

    /**
     * Memastikan permintaan yang sudah berstatus 'Confirmed' tidak dapat diubah lagi.
     */
    it('cannot edit orders that are already Confirmed', function () {
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

    /**
     * Memastikan update data pada permintaan berstatus 'Pending' berhasil dan status tetap 'Pending'.
     */
    it('can update Pending orders and status remains Pending', function () {
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

    /**
     * Memastikan user dilarang memperbarui data pada permintaan yang sudah 'Confirmed'.
     */
    it('cannot update Confirmed orders', function () {
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

    /**
     * Memastikan permintaan berstatus 'Pending' dapat dihapus oleh user.
     */
    it('can delete Pending orders', function () {
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

    /**
     * Memastikan permintaan yang sudah 'Confirmed' tidak dapat dihapus.
     */
    it('cannot delete Confirmed orders', function () {
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

describe('Confirmation & Rejection (Permission: ConfirmWarehouseOrder)', function () {

    /**
     * Memastikan user dengan izin 'konfirmasi_permintaan_barang' dapat menyetujui permintaan berstatus 'Pending'.
     */
    it('allows users with ConfirmWarehouseOrder to confirm Pending orders', function () {
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

    /**
     * Memastikan user tanpa izin 'konfirmasi_permintaan_barang' ditolak saat mencoba menyetujui permintaan.
     */
    it('denies confirmation action for users without ConfirmWarehouseOrder', function () {
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

    /**
     * Memastikan user dengan izin 'konfirmasi_permintaan_barang' dapat menolak permintaan berstatus 'Pending'.
     */
    it('allows users with ConfirmWarehouseOrder to reject Pending orders', function () {
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

    /**
     * Memastikan user tanpa izin 'konfirmasi_permintaan_barang' ditolak saat mencoba menolak permintaan.
     */
    it('denies rejection action for users without ConfirmWarehouseOrder', function () {
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

    /**
     * Memastikan permintaan berstatus 'Revision' juga dapat disetujui setelah diperbaiki.
     */
    it('allows users with ConfirmWarehouseOrder to confirm Revision orders', function () {
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

describe('Handover Item (Permission: HandoverItem)', function () {

    /**
     * Memastikan user dengan izin 'serah_terima_barang' dapat mengakses form serah terima (delivery).
     */
    it('allows users with HandoverItem permission to access delivery page', function () {
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

    /**
     * Memastikan user tanpa izin 'serah_terima_barang' ditolak saat mengakses halaman delivery.
     */
    it('denies users without HandoverItem permission from accessing delivery page', function () {
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

    /**
     * Memastikan status permintaan berubah menjadi 'Delivered' setelah proses delivery selesai.
     */
    it('successfully changes status to Delivered after delivery', function () {
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

describe('Receive Items (Permission: ReceiveItem)', function () {

    /**
     * Memastikan user dengan izin 'terima_barang' dapat mengakses halaman form penerimaan (receive).
     */
    it('allows users with ReceiveItem permission to access receive page', function () {
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

    /**
     * Memastikan user tanpa izin 'terima_barang' ditolak saat mencoba mengakses halaman form penerimaan.
     */
    it('denies users without ReceiveItem permission from accessing receive page', function () {
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

    /**
     * Memastikan status permintaan berubah menjadi 'Finished' setelah user mengkonfirmasi penerimaan barang.
     */
    it('successfully changes status to Finished after receive', function () {
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

describe('Delivery: Item Transaction Tracking (Outbound Stock)', function () {

    /**
     * Memastikan setiap item dalam delivery dicatat sebagai transaksi 'Out' di database.
     */
    it('records an Outbound item_transaction for each item during delivery', function () {
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

describe('Receive: Item Transaction Tracking (Inbound Stock)', function () {

    /**
     * Memastikan setiap item yang diterima dicatat sebagai transaksi 'In' pada inventaris divisi terkait.
     */
    it('records an Inbound item_transaction for each item during receive', function () {
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

    /**
     * Memastikan stok inventaris divisi peminta bertambah sesuai dengan jumlah barang yang diterima.
     */
    it('increases division item stock by the received quantity', function () {
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

describe('Datatable: Pagination, Limit, and Search', function () {

    /**
     * Memastikan mekanisme pagination (pindah halaman) bekerja dengan benar pada datatable.
     */
    it('handles pagination correctly', function () {
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

    /**
     * Memastikan pengaturan jumlah data per halaman (limit) bekerja sesuai input user.
     */
    it('handles limit parameter correctly', function () {
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

    /**
     * Memastikan fitur search global dapat menemukan permintaan berdasarkan nomor order.
     */
    it('can search for orders by order_number', function () {
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

    /**
     * Memastikan fitur search global dapat menemukan permintaan berdasarkan deskripsi/keterangan.
     */
    it('can search for orders by description', function () {
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

    /**
     * Memastikan filter pencarian spesifik pada kolom nomor order (order_number) bekerja.
     */
    it('filters datatable by order_number column', function () {
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

    /**
     * Memastikan filter pencarian berdasarkan status permintaan (Pending, Confirmed, etc) bekerja.
     */
    it('filters datatable by status column', function () {
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

    /**
     * Memastikan filter pencarian berdasarkan ID divisi peminta bekerja.
     */
    it('filters datatable by division_id column', function () {
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

    /**
     * Memastikan filter pencarian berdasarkan nama user (peminta) bekerja.
     */
    it('filters datatable by user_id (user name)', function () {
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

    /**
     * Memastikan filter pencarian berdasarkan bulan dan tahun pembuatan (created_at) bekerja.
     */
    it('filters datatable by created_at (month/year)', function () {
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

    /**
     * Memastikan opsi filter 'ALL' pada status mengembalikan seluruh data permintaan.
     */
    it('returns all data when status filter is set to ALL', function () {
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

    /**
     * Memastikan opsi filter 'ALL' pada divisi mengembalikan data permintaan dari seluruh divisi.
     */
    it('returns all data when division_id filter is set to ALL', function () {
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
