<?php

use App\Models\Division;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Inventory\Enums\InventoryPermission;
use Modules\Inventory\Enums\ItemTransactionType;
use Modules\Inventory\Models\CategoryItem;
use Modules\Inventory\Models\Item;
use Modules\Inventory\Models\ItemTransaction;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $permissions = [
        InventoryPermission::MonitorItemTransaction->value,
        InventoryPermission::MonitorAllItemTransaction->value,
    ];

    foreach ($permissions as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }
});

it('can display item transaction index for authorized user', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(InventoryPermission::MonitorItemTransaction->value);

    $response = $this->actingAs($user)->get('/inventory/transactions');

    $response->assertOk();
});

it('denies access for unauthorized user', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/inventory/transactions');

    $response->assertForbidden();
});

it('returns datatable data with permission filter', function () {
    $division = Division::factory()->create();
    $user = User::factory()->create(['division_id' => $division->id]);
    $user->givePermissionTo(InventoryPermission::MonitorItemTransaction->value);

    $category = CategoryItem::create(['name' => 'Test', 'is_active' => true]);
    $item = Item::create([
        'division_id' => $division->id,
        'category_id' => $category->id,
        'name' => 'Test Item',
        'unit_of_measure' => 'pcs',
        'stock' => 100,
    ]);

    ItemTransaction::create([
        'date' => now(),
        'type' => ItemTransactionType::In,
        'item_id' => $item->id,
        'quantity' => 10,
        'user_id' => $user->id,
        'description' => 'Test transaction',
    ]);

    $response = $this->actingAs($user)->get('/inventory/transactions/datatable');

    $response->assertOk();
});

it('can view all transactions with proper permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(InventoryPermission::MonitorAllItemTransaction->value);

    $category = CategoryItem::create(['name' => 'Test', 'is_active' => true]);

    // Create item in main warehouse
    $item = Item::create([
        'category_id' => $category->id,
        'name' => 'Main Warehouse Item',
        'unit_of_measure' => 'pcs',
        'stock' => 200,
    ]);

    ItemTransaction::create([
        'date' => now(),
        'type' => ItemTransactionType::In,
        'item_id' => $item->id,
        'quantity' => 50,
        'user_id' => $user->id,
        'description' => 'Stock masuk',
    ]);

    $response = $this->actingAs($user)->get('/inventory/transactions/datatable');

    $response->assertOk();
});

it('filters transactions by type', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(InventoryPermission::MonitorAllItemTransaction->value);

    $response = $this->actingAs($user)->get('/inventory/transactions/datatable?type=In');

    $response->assertOk();
});

it('filters transactions by date', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(InventoryPermission::MonitorAllItemTransaction->value);

    $category = CategoryItem::create(['name' => 'Test', 'is_active' => true]);
    $item = Item::create(['category_id' => $category->id, 'name' => 'Test Item', 'unit_of_measure' => 'pcs', 'stock' => 100]);

    ItemTransaction::create([
        'date' => '2023-01-15',
        'type' => ItemTransactionType::In,
        'item_id' => $item->id,
        'quantity' => 10,
        'user_id' => $user->id,
    ]);

    ItemTransaction::create([
        'date' => '2023-02-15',
        'type' => ItemTransactionType::In,
        'item_id' => $item->id,
        'quantity' => 10,
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->get('/inventory/transactions/datatable?date=2023-01');

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.date', '15 Januari 2023');
});

it('exports excel successfully', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(InventoryPermission::MonitorAllItemTransaction->value);

    $response = $this->actingAs($user)->get('/inventory/transactions/print-excel');

    $response->assertOk();
});

it('filters transactions by user name', function () {
    $user1 = User::factory()->create(['name' => 'Budi']);
    $user2 = User::factory()->create(['name' => 'Andi']);
    $user1->givePermissionTo(InventoryPermission::MonitorAllItemTransaction->value);

    $category = CategoryItem::create(['name' => 'Test', 'is_active' => true]);
    $item = Item::create(['category_id' => $category->id, 'name' => 'Test Item', 'unit_of_measure' => 'pcs', 'stock' => 100]);

    ItemTransaction::create([
        'date' => now(),
        'type' => ItemTransactionType::In,
        'item_id' => $item->id,
        'quantity' => 10,
        'user_id' => $user1->id,
    ]);

    ItemTransaction::create([
        'date' => now(),
        'type' => ItemTransactionType::In,
        'item_id' => $item->id,
        'quantity' => 10,
        'user_id' => $user2->id,
    ]);

    $response = $this->actingAs($user1)->get('/inventory/transactions/datatable?user_name=Budi');

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.user', 'Budi');
});

it('filters transactions by description', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(InventoryPermission::MonitorAllItemTransaction->value);

    $category = CategoryItem::create(['name' => 'Test', 'is_active' => true]);
    $item = Item::create(['category_id' => $category->id, 'name' => 'Test Item', 'unit_of_measure' => 'pcs', 'stock' => 100]);

    ItemTransaction::create([
        'date' => now(),
        'type' => ItemTransactionType::In,
        'item_id' => $item->id,
        'quantity' => 10,
        'user_id' => $user->id,
        'description' => 'Pembelian Barang',
    ]);

    ItemTransaction::create([
        'date' => now(),
        'type' => ItemTransactionType::In,
        'item_id' => $item->id,
        'quantity' => 10,
        'user_id' => $user->id,
        'description' => 'Retur Barang',
    ]);

    $response = $this->actingAs($user)->get('/inventory/transactions/datatable?description=Pembelian');

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.description', 'Pembelian Barang');
});

it('shows division column for users with monitor all permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(InventoryPermission::MonitorAllItemTransaction->value);

    $response = $this->actingAs($user)->get('/inventory/transactions');

    $response->assertOk();
    expect($response->inertiaPage()['component'])->toBe('Inventory/ItemTransaction/Index');
});

it('menampilkan transaksi dari konfirmasi stock opname sebagai tipe Stock Opname', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(InventoryPermission::MonitorAllItemTransaction->value);

    $category = CategoryItem::create(['name' => 'Test', 'is_active' => true]);
    $item = Item::create(['category_id' => $category->id, 'name' => 'Test Item', 'unit_of_measure' => 'pcs', 'stock' => 100]);

    ItemTransaction::create([
        'date' => now(),
        'type' => ItemTransactionType::StockOpname,
        'item_id' => $item->id,
        'quantity' => 5,
        'user_id' => $user->id,
        'description' => 'Selisih SO',
    ]);

    $response = $this->actingAs($user)->get('/inventory/transactions/datatable?type=Stock Opname');

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.type', 'Stock Opname');
});

it('dapat mengurutkan transaksi berdasarkan jumlah', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(InventoryPermission::MonitorAllItemTransaction->value);

    $category = CategoryItem::create(['name' => 'Test', 'is_active' => true]);
    $item = Item::create(['category_id' => $category->id, 'name' => 'Test Item', 'unit_of_measure' => 'pcs', 'stock' => 100]);

    ItemTransaction::create([
        'date' => now(),
        'type' => ItemTransactionType::In,
        'item_id' => $item->id,
        'quantity' => 10,
        'user_id' => $user->id,
    ]);

    ItemTransaction::create([
        'date' => now(),
        'type' => ItemTransactionType::In,
        'item_id' => $item->id,
        'quantity' => 50,
        'user_id' => $user->id,
    ]);

    // Descending sort (default or explicit)
    $response = $this->actingAs($user)->get('/inventory/transactions/datatable?sort_by=quantity&sort_direction=desc');

    $response->assertOk();
    $response->assertJsonPath('data.0.quantity', 50);
    $response->assertJsonPath('data.1.quantity', 10);
});

it('user dengan monitor_transaksi_barang hanya melihat transaksi dari division sendiri', function () {
    $divisionA = Division::factory()->create();
    $divisionB = Division::factory()->create();

    $userA = User::factory()->create(['division_id' => $divisionA->id]);
    $userA->givePermissionTo(InventoryPermission::MonitorItemTransaction->value);

    $category = CategoryItem::create(['name' => 'Test', 'is_active' => true]);

    // Create item in division A
    $itemA = Item::create([
        'division_id' => $divisionA->id,
        'category_id' => $category->id,
        'name' => 'Item Division A',
        'unit_of_measure' => 'pcs',
        'stock' => 100,
    ]);

    // Create item in division B
    $itemB = Item::create([
        'division_id' => $divisionB->id,
        'category_id' => $category->id,
        'name' => 'Item Division B',
        'unit_of_measure' => 'pcs',
        'stock' => 100,
    ]);

    // Create transaction in division A
    ItemTransaction::create([
        'date' => now(),
        'type' => ItemTransactionType::In,
        'item_id' => $itemA->id,
        'quantity' => 10,
        'user_id' => $userA->id,
    ]);

    // Create transaction in division B
    ItemTransaction::create([
        'date' => now(),
        'type' => ItemTransactionType::In,
        'item_id' => $itemB->id,
        'quantity' => 20,
        'user_id' => $userA->id,
    ]);

    $response = $this->actingAs($userA)->get('/inventory/transactions/datatable');

    $response->assertOk();
    $response->assertJsonCount(1, 'data'); // Only sees division A
    $response->assertJsonPath('data.0.item', 'Item Division A');
});

it('user dengan monitor_semua_transaksi_barang melihat transaksi dari semua division', function () {
    $divisionA = Division::factory()->create();
    $divisionB = Division::factory()->create();

    $user = User::factory()->create();
    $user->givePermissionTo(InventoryPermission::MonitorAllItemTransaction->value);

    $category = CategoryItem::create(['name' => 'Test', 'is_active' => true]);

    $itemA = Item::create([
        'division_id' => $divisionA->id,
        'category_id' => $category->id,
        'name' => 'Item A',
        'unit_of_measure' => 'pcs',
        'stock' => 100,
    ]);

    $itemB = Item::create([
        'division_id' => $divisionB->id,
        'category_id' => $category->id,
        'name' => 'Item B',
        'unit_of_measure' => 'pcs',
        'stock' => 100,
    ]);

    // Main warehouse item (no division)
    $itemMain = Item::create([
        'category_id' => $category->id,
        'name' => 'Item Gudang Utama',
        'unit_of_measure' => 'pcs',
        'stock' => 100,
    ]);

    ItemTransaction::create([
        'date' => now(),
        'type' => ItemTransactionType::In,
        'item_id' => $itemA->id,
        'quantity' => 10,
        'user_id' => $user->id,
    ]);

    ItemTransaction::create([
        'date' => now(),
        'type' => ItemTransactionType::In,
        'item_id' => $itemB->id,
        'quantity' => 20,
        'user_id' => $user->id,
    ]);

    ItemTransaction::create([
        'date' => now(),
        'type' => ItemTransactionType::In,
        'item_id' => $itemMain->id,
        'quantity' => 30,
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->get('/inventory/transactions/datatable');

    $response->assertOk();
    $response->assertJsonCount(3, 'data'); // Sees ALL divisions including main warehouse
});

it('user tanpa permission tidak dapat melihat transaksi apapun', function () {
    $user = User::factory()->create();
    // No permission given

    $response = $this->actingAs($user)->get('/inventory/transactions/datatable');

    $response->assertForbidden();
});

it('user dengan monitor_transaksi_barang tidak melihat transaksi gudang utama', function () {
    $division = Division::factory()->create();
    $user = User::factory()->create(['division_id' => $division->id]);
    $user->givePermissionTo(InventoryPermission::MonitorItemTransaction->value);

    $category = CategoryItem::create(['name' => 'Test', 'is_active' => true]);

    // Create main warehouse item (no division_id)
    $mainItem = Item::create([
        'category_id' => $category->id,
        'name' => 'Main Warehouse Item',
        'unit_of_measure' => 'pcs',
        'stock' => 100,
    ]);

    // Create division item
    $divisionItem = Item::create([
        'division_id' => $division->id,
        'category_id' => $category->id,
        'name' => 'Division Item',
        'unit_of_measure' => 'pcs',
        'stock' => 100,
    ]);

    // Create transactions
    ItemTransaction::create([
        'date' => now(),
        'type' => ItemTransactionType::In,
        'item_id' => $mainItem->id,
        'quantity' => 10,
        'user_id' => $user->id,
    ]);

    ItemTransaction::create([
        'date' => now(),
        'type' => ItemTransactionType::In,
        'item_id' => $divisionItem->id,
        'quantity' => 25,
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->get('/inventory/transactions/datatable');

    $response->assertOk();
    $response->assertJsonCount(1, 'data'); // Only sees division item, not main warehouse
    $response->assertJsonPath('data.0.item', 'Division Item');
});
