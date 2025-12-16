<?php

use App\Models\Division;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Inventory\Models\CategoryItem;
use Modules\Inventory\Models\Item;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $permissions = [
        'monitor_stok',
        'monitor_semua_stok',
        'keluarkan_stok',
    ];

    foreach ($permissions as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }
});

it('can display stock monitoring index for authorized user', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('monitor_stok');

    $response = $this->actingAs($user)->get('/inventory/stock-monitoring');

    $response->assertOk();
});

it('denies access for unauthorized user', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/inventory/stock-monitoring');

    $response->assertForbidden();
});

it('returns datatable data', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('monitor_stok');

    $response = $this->actingAs($user)->get('/inventory/stock-monitoring/datatable');

    $response->assertOk();
});

it('can view all stock with proper permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('monitor_semua_stok');

    $category = CategoryItem::create(['name' => 'Test', 'is_active' => true]);

    Item::create([
        'category_id' => $category->id,
        'name' => 'Main Warehouse Item',
        'unit_of_measure' => 'pcs',
        'stock' => 100,
    ]);

    $division = Division::factory()->create();
    Item::create([
        'division_id' => $division->id,
        'category_id' => $category->id,
        'name' => 'Division Item',
        'unit_of_measure' => 'pcs',
        'stock' => 50,
    ]);

    $response = $this->actingAs($user)->get('/inventory/stock-monitoring/datatable');

    $response->assertOk();
});

it('filters stock by division for regular user', function () {
    $division = Division::factory()->create();
    $user = User::factory()->create(['division_id' => $division->id]);
    $user->givePermissionTo('monitor_stok');

    $response = $this->actingAs($user)->get('/inventory/stock-monitoring/datatable');

    $response->assertOk();
});

it('can issue stock from monitoring', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('keluarkan_stok');

    $category = CategoryItem::create(['name' => 'Test', 'is_active' => true]);
    $item = Item::create([
        'category_id' => $category->id,
        'name' => 'Test Item',
        'unit_of_measure' => 'pcs',
        'stock' => 100,
    ]);

    $response = $this->actingAs($user)->post("/inventory/stock-monitoring/{$item->id}/issue", [
        'quantity' => 10,
        'description' => 'Issued from monitoring',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('items', [
        'id' => $item->id,
        'stock' => 90,
    ]);
});

it('can convert item units from monitoring', function () {
    $division = Division::factory()->create();
    $user = User::factory()->create(['division_id' => $division->id]);
    $user->givePermissionTo('monitor_stok');

    $category = CategoryItem::create(['name' => 'Test', 'is_active' => true]);

    // Create base item (units) in division
    $pcsItem = Item::create([
        'division_id' => $division->id,
        'category_id' => $category->id,
        'name' => 'Item Pcs',
        'unit_of_measure' => 'pcs',
        'stock' => 0,
        'multiplier' => 1,
    ]);

    // Create pack item that references base item
    $boxItem = Item::create([
        'division_id' => $division->id,
        'category_id' => $category->id,
        'name' => 'Item Box',
        'unit_of_measure' => 'box',
        'stock' => 10,
        'multiplier' => 10,
        'reference_item_id' => $pcsItem->id,
    ]);

    // Convert 3 boxes to 30 pcs
    $response = $this->actingAs($user)->post("/inventory/stock-monitoring/{$boxItem->id}/convert", [
        'quantity' => 3,
    ]);

    $response->assertRedirect();
    // 10 - 3 = 7 boxes, 0 + 30 = 30 pcs
    $this->assertDatabaseHas('items', ['id' => $boxItem->id, 'stock' => 7]);
    $this->assertDatabaseHas('items', ['id' => $pcsItem->id, 'stock' => 30]);
});

it('exports excel successfully', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('monitor_semua_stok');

    $response = $this->actingAs($user)->get('/inventory/stock-monitoring/print-excel');

    $response->assertOk();
});

it('filters stock by max stock', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('monitor_semua_stok');

    $category = CategoryItem::create(['name' => 'Test', 'is_active' => true]);
    $division = Division::factory()->create();

    Item::create([
        'division_id' => $division->id,
        'category_id' => $category->id,
        'name' => 'High Stock',
        'unit_of_measure' => 'pcs',
        'stock' => 100,
    ]);

    Item::create([
        'division_id' => $division->id,
        'category_id' => $category->id,
        'name' => 'Low Stock',
        'unit_of_measure' => 'pcs',
        'stock' => 10,
    ]);

    $response = $this->actingAs($user)->get('/inventory/stock-monitoring/datatable?stock_max=50');

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.name', 'Low Stock');
});

it('filters stock by unit of measure', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('monitor_semua_stok');

    $category = CategoryItem::create(['name' => 'Test', 'is_active' => true]);
    $division = Division::factory()->create();

    Item::create([
        'division_id' => $division->id,
        'category_id' => $category->id,
        'name' => 'Box Item',
        'unit_of_measure' => 'box',
        'stock' => 100,
    ]);

    Item::create([
        'division_id' => $division->id,
        'category_id' => $category->id,
        'name' => 'Pcs Item',
        'unit_of_measure' => 'pcs',
        'stock' => 100,
    ]);

    $response = $this->actingAs($user)->get('/inventory/stock-monitoring/datatable?unit_of_measure=box');

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.unit_of_measure', 'box');
});

it('dapat mengurutkan stok berdasarkan nama barang', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('monitor_semua_stok');
    $division = Division::factory()->create();

    $category = CategoryItem::create(['name' => 'Test', 'is_active' => true]);

    Item::create([
        'division_id' => $division->id,
        'category_id' => $category->id,
        'name' => 'Zebra Item',
        'unit_of_measure' => 'pcs',
        'stock' => 10,
    ]);

    Item::create([
        'division_id' => $division->id,
        'category_id' => $category->id,
        'name' => 'Alpha Item',
        'unit_of_measure' => 'pcs',
        'stock' => 10,
    ]);

    $response = $this->actingAs($user)->get('/inventory/stock-monitoring/datatable?sort_by=name&sort_direction=asc');

    $response->assertOk();
    $response->assertJsonPath('data.0.name', 'Alpha Item');
    $response->assertJsonPath('data.1.name', 'Zebra Item');
});

it('user dengan monitor_stok hanya melihat stok dari division sendiri', function () {
    $divisionA = Division::factory()->create();
    $divisionB = Division::factory()->create();

    $userA = User::factory()->create(['division_id' => $divisionA->id]);
    $userA->givePermissionTo('monitor_stok');

    $category = CategoryItem::create(['name' => 'Test', 'is_active' => true]);

    // Create item in division A
    Item::create([
        'division_id' => $divisionA->id,
        'category_id' => $category->id,
        'name' => 'Item Division A',
        'unit_of_measure' => 'pcs',
        'stock' => 100,
    ]);

    // Create item in division B
    Item::create([
        'division_id' => $divisionB->id,
        'category_id' => $category->id,
        'name' => 'Item Division B',
        'unit_of_measure' => 'pcs',
        'stock' => 50,
    ]);

    $response = $this->actingAs($userA)->get('/inventory/stock-monitoring/datatable');

    $response->assertOk();
    $response->assertJsonCount(1, 'data'); // Only sees division A
    $response->assertJsonPath('data.0.name', 'Item Division A');
});

it('user dengan monitor_semua_stok melihat stok dari semua division', function () {
    $divisionA = Division::factory()->create();
    $divisionB = Division::factory()->create();

    $user = User::factory()->create();
    $user->givePermissionTo('monitor_semua_stok');

    $category = CategoryItem::create(['name' => 'Test', 'is_active' => true]);

    Item::create([
        'division_id' => $divisionA->id,
        'category_id' => $category->id,
        'name' => 'Item A',
        'unit_of_measure' => 'pcs',
        'stock' => 100,
    ]);

    Item::create([
        'division_id' => $divisionB->id,
        'category_id' => $category->id,
        'name' => 'Item B',
        'unit_of_measure' => 'pcs',
        'stock' => 50,
    ]);

    // Main warehouse item (no division)
    Item::create([
        'category_id' => $category->id,
        'name' => 'Item Gudang Utama',
        'unit_of_measure' => 'pcs',
        'stock' => 200,
    ]);

    $response = $this->actingAs($user)->get('/inventory/stock-monitoring/datatable');

    $response->assertOk();
    $response->assertJsonCount(3, 'data'); // Sees ALL divisions including main warehouse
});

it('user tanpa permission tidak dapat melihat stok apapun', function () {
    $user = User::factory()->create();
    // No permission given

    $response = $this->actingAs($user)->get('/inventory/stock-monitoring/datatable');

    $response->assertForbidden();
});

it('user dengan monitor_stok tidak melihat stok gudang utama', function () {
    $division = Division::factory()->create();
    $user = User::factory()->create(['division_id' => $division->id]);
    $user->givePermissionTo('monitor_stok');

    $category = CategoryItem::create(['name' => 'Test', 'is_active' => true]);

    // Create main warehouse item (no division_id)
    Item::create([
        'category_id' => $category->id,
        'name' => 'Main Warehouse Item',
        'unit_of_measure' => 'pcs',
        'stock' => 100,
    ]);

    // Create division item
    Item::create([
        'division_id' => $division->id,
        'category_id' => $category->id,
        'name' => 'Division Item',
        'unit_of_measure' => 'pcs',
        'stock' => 50,
    ]);

    $response = $this->actingAs($user)->get('/inventory/stock-monitoring/datatable');

    $response->assertOk();
    $response->assertJsonCount(1, 'data'); // Only sees division item, not main warehouse
    $response->assertJsonPath('data.0.name', 'Division Item');
});
