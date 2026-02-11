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
    // Buat semua permission inventory
    $permissions = InventoryPermission::values();
    foreach ($permissions as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }

    // Shared fixtures
    $this->divisionA = Division::factory()->create(['name' => 'Divisi A']);
    $this->divisionB = Division::factory()->create(['name' => 'Divisi B']);
    $this->category = CategoryItem::create(['name' => 'ATK', 'is_active' => true]);
    $this->category2 = CategoryItem::create(['name' => 'Elektronik', 'is_active' => true]);
});

// ============================================================================
// 1. Akses halaman index dan print berdasarkan permission
// ============================================================================

describe('Akses Halaman (Permission: MonitorStock / MonitorAllStock)', function () {

    it('user dengan MonitorStock dapat mengakses halaman index', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::MonitorStock->value);

        $this->actingAs($user)->get('/inventory/stock-monitoring')
            ->assertOk();
    });

    it('user dengan MonitorAllStock dapat mengakses halaman index', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(InventoryPermission::MonitorAllStock->value);

        $this->actingAs($user)->get('/inventory/stock-monitoring')
            ->assertOk();
    });

    it('user tanpa permission tidak dapat mengakses halaman index', function () {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/inventory/stock-monitoring')
            ->assertForbidden();
    });

    it('user dengan MonitorStock dapat mengakses print-excel', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::MonitorStock->value);

        $this->actingAs($user)->get('/inventory/stock-monitoring/print-excel')
            ->assertOk();
    });

    it('user dengan MonitorAllStock dapat mengakses print-excel', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(InventoryPermission::MonitorAllStock->value);

        $this->actingAs($user)->get('/inventory/stock-monitoring/print-excel')
            ->assertOk();
    });

    it('user tanpa permission tidak dapat mengakses print-excel', function () {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/inventory/stock-monitoring/print-excel')
            ->assertForbidden();
    });

    it('user tanpa permission tidak dapat mengakses datatable', function () {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/inventory/stock-monitoring/datatable')
            ->assertForbidden();
    });
});

// ============================================================================
// 2. Datatable: MonitorStock filter division_id = user login
// ============================================================================

describe('Datatable Filter Division (MonitorStock)', function () {

    it('user dengan MonitorStock hanya melihat item divisi sendiri', function () {
        $userA = User::factory()->create(['division_id' => $this->divisionA->id]);
        $userA->givePermissionTo(InventoryPermission::MonitorStock->value);

        Item::create([
            'division_id' => $this->divisionA->id,
            'category_id' => $this->category->id,
            'name' => 'Item Divisi A',
            'unit_of_measure' => 'pcs',
            'stock' => 100,
        ]);

        Item::create([
            'division_id' => $this->divisionB->id,
            'category_id' => $this->category->id,
            'name' => 'Item Divisi B',
            'unit_of_measure' => 'pcs',
            'stock' => 50,
        ]);

        // Item gudang utama (tanpa division)
        Item::create([
            'category_id' => $this->category->id,
            'name' => 'Item Gudang Utama',
            'unit_of_measure' => 'pcs',
            'stock' => 200,
        ]);

        $response = $this->actingAs($userA)->get('/inventory/stock-monitoring/datatable');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Item Divisi A');
    });
});

// ============================================================================
// 3. Datatable: MonitorAllStock melihat semua division (kecuali gudang utama)
// ============================================================================

describe('Datatable Filter Division (MonitorAllStock)', function () {

    it('user dengan MonitorAllStock melihat semua item divisi', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(InventoryPermission::MonitorAllStock->value);

        Item::create([
            'division_id' => $this->divisionA->id,
            'category_id' => $this->category->id,
            'name' => 'Item A',
            'unit_of_measure' => 'pcs',
            'stock' => 100,
        ]);

        Item::create([
            'division_id' => $this->divisionB->id,
            'category_id' => $this->category->id,
            'name' => 'Item B',
            'unit_of_measure' => 'pcs',
            'stock' => 50,
        ]);

        // Item gudang utama — tidak seharusnya muncul
        Item::create([
            'category_id' => $this->category->id,
            'name' => 'Item Gudang Utama',
            'unit_of_measure' => 'pcs',
            'stock' => 200,
        ]);

        $response = $this->actingAs($user)->get('/inventory/stock-monitoring/datatable');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    });

    it('user MonitorAllStock bisa lihat division_id yang beda dengan miliknya', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::MonitorAllStock->value);

        Item::create([
            'division_id' => $this->divisionB->id,
            'category_id' => $this->category->id,
            'name' => 'Item Divisi Lain',
            'unit_of_measure' => 'pcs',
            'stock' => 75,
        ]);

        $response = $this->actingAs($user)->get('/inventory/stock-monitoring/datatable');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Item Divisi Lain');
    });
});

// ============================================================================
// 4. Issue stock memerlukan permission IssueStock
// ============================================================================

describe('Issue Stock (Permission: IssueStock)', function () {

    it('user dengan IssueStock dapat mengakses halaman issue', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::IssueStock->value);

        $item = Item::create([
            'division_id' => $this->divisionA->id,
            'category_id' => $this->category->id,
            'name' => 'Item Issue',
            'unit_of_measure' => 'pcs',
            'stock' => 50,
        ]);

        $this->actingAs($user)->get("/inventory/stock-monitoring/{$item->id}/issue")
            ->assertOk();
    });

    it('user tanpa IssueStock tidak dapat mengakses issue', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);

        $item = Item::create([
            'division_id' => $this->divisionA->id,
            'category_id' => $this->category->id,
            'name' => 'Item Issue',
            'unit_of_measure' => 'pcs',
            'stock' => 50,
        ]);

        $this->actingAs($user)->get("/inventory/stock-monitoring/{$item->id}/issue")
            ->assertForbidden();
    });

    it('issue stock: stok berkurang dan item_transaction tercatat', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::IssueStock->value);

        $item = Item::create([
            'division_id' => $this->divisionA->id,
            'category_id' => $this->category->id,
            'name' => 'Item Issue Test',
            'unit_of_measure' => 'pcs',
            'stock' => 100,
        ]);

        $response = $this->actingAs($user)->post("/inventory/stock-monitoring/{$item->id}/issue", [
            'quantity' => 10,
            'description' => 'Pengeluaran untuk keperluan rapat',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'stock' => 90,
        ]);

        $this->assertDatabaseHas('item_transactions', [
            'item_id' => $item->id,
            'type' => ItemTransactionType::Out->value,
            'quantity' => 10,
            'user_id' => $user->id,
        ]);
    });
});

// ============================================================================
// 5. Datatable: Pagination, Limit, dan Search Global
// ============================================================================

describe('Datatable: Pagination, Limit, dan Search Global', function () {

    it('pagination bekerja dengan benar (default 20 per halaman)', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(InventoryPermission::MonitorAllStock->value);

        for ($i = 1; $i <= 25; $i++) {
            Item::create([
                'division_id' => $this->divisionA->id,
                'category_id' => $this->category->id,
                'name' => 'Item Paging ' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'unit_of_measure' => 'pcs',
                'stock' => $i * 10,
            ]);
        }

        $response = $this->actingAs($user)->get('/inventory/stock-monitoring/datatable');
        $response->assertOk();
        $response->assertJsonCount(20, 'data');
        $response->assertJsonPath('last_page', 2);
        $response->assertJsonPath('total', 25);

        $response2 = $this->actingAs($user)->get('/inventory/stock-monitoring/datatable?page=2');
        $response2->assertOk();
        $response2->assertJsonCount(5, 'data');
    });

    it('limit bekerja dengan benar', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(InventoryPermission::MonitorAllStock->value);

        for ($i = 1; $i <= 15; $i++) {
            Item::create([
                'division_id' => $this->divisionA->id,
                'category_id' => $this->category->id,
                'name' => 'Item Limit ' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'unit_of_measure' => 'pcs',
                'stock' => 10,
            ]);
        }

        $response = $this->actingAs($user)->get('/inventory/stock-monitoring/datatable?limit=5');
        $response->assertOk();
        $response->assertJsonCount(5, 'data');
        $response->assertJsonPath('last_page', 3);
        $response->assertJsonPath('total', 15);
    });

    it('search global berdasarkan nama item', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(InventoryPermission::MonitorAllStock->value);

        Item::create([
            'division_id' => $this->divisionA->id,
            'category_id' => $this->category->id,
            'name' => 'Kertas HVS A4',
            'unit_of_measure' => 'rim',
            'stock' => 100,
        ]);

        Item::create([
            'division_id' => $this->divisionA->id,
            'category_id' => $this->category->id,
            'name' => 'Pulpen Hitam',
            'unit_of_measure' => 'pcs',
            'stock' => 200,
        ]);

        $response = $this->actingAs($user)->get('/inventory/stock-monitoring/datatable?search=Kertas');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Kertas HVS A4');
    });

    it('search global berdasarkan unit_of_measure', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(InventoryPermission::MonitorAllStock->value);

        Item::create([
            'division_id' => $this->divisionA->id,
            'category_id' => $this->category->id,
            'name' => 'Item Rim',
            'unit_of_measure' => 'rim',
            'stock' => 100,
        ]);

        Item::create([
            'division_id' => $this->divisionA->id,
            'category_id' => $this->category->id,
            'name' => 'Item Pcs',
            'unit_of_measure' => 'pcs',
            'stock' => 200,
        ]);

        $response = $this->actingAs($user)->get('/inventory/stock-monitoring/datatable?search=rim');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Item Rim');
    });
});

// ============================================================================
// 6. Datatable: Individual Column Filter
// ============================================================================

describe('Datatable: Individual Column Filter (Footer Search)', function () {

    it('filter berdasarkan kolom name', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(InventoryPermission::MonitorAllStock->value);

        Item::create([
            'division_id' => $this->divisionA->id,
            'category_id' => $this->category->id,
            'name' => 'Kertas HVS',
            'unit_of_measure' => 'rim',
            'stock' => 100,
        ]);

        Item::create([
            'division_id' => $this->divisionA->id,
            'category_id' => $this->category->id,
            'name' => 'Pulpen Hitam',
            'unit_of_measure' => 'pcs',
            'stock' => 200,
        ]);

        $response = $this->actingAs($user)->get('/inventory/stock-monitoring/datatable?name=Kertas');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Kertas HVS');
    });

    it('filter berdasarkan kolom category_id', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(InventoryPermission::MonitorAllStock->value);

        Item::create([
            'division_id' => $this->divisionA->id,
            'category_id' => $this->category->id,
            'name' => 'Item ATK',
            'unit_of_measure' => 'pcs',
            'stock' => 100,
        ]);

        Item::create([
            'division_id' => $this->divisionA->id,
            'category_id' => $this->category2->id,
            'name' => 'Item Elektronik',
            'unit_of_measure' => 'unit',
            'stock' => 50,
        ]);

        $response = $this->actingAs($user)->get("/inventory/stock-monitoring/datatable?category_id={$this->category->id}");
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Item ATK');
    });

    it('filter category_id ALL mengambil semua kategori', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(InventoryPermission::MonitorAllStock->value);

        Item::create([
            'division_id' => $this->divisionA->id,
            'category_id' => $this->category->id,
            'name' => 'Item ATK',
            'unit_of_measure' => 'pcs',
            'stock' => 100,
        ]);

        Item::create([
            'division_id' => $this->divisionA->id,
            'category_id' => $this->category2->id,
            'name' => 'Item Elektronik',
            'unit_of_measure' => 'unit',
            'stock' => 50,
        ]);

        $response = $this->actingAs($user)->get('/inventory/stock-monitoring/datatable?category_id=ALL');
        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    });

    it('filter berdasarkan kolom stock (minimum)', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(InventoryPermission::MonitorAllStock->value);

        Item::create([
            'division_id' => $this->divisionA->id,
            'category_id' => $this->category->id,
            'name' => 'Stok Tinggi',
            'unit_of_measure' => 'pcs',
            'stock' => 100,
        ]);

        Item::create([
            'division_id' => $this->divisionA->id,
            'category_id' => $this->category->id,
            'name' => 'Stok Rendah',
            'unit_of_measure' => 'pcs',
            'stock' => 10,
        ]);

        $response = $this->actingAs($user)->get('/inventory/stock-monitoring/datatable?stock=50');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Stok Tinggi');
    });

    it('filter berdasarkan kolom stock_max (maksimum)', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(InventoryPermission::MonitorAllStock->value);

        Item::create([
            'division_id' => $this->divisionA->id,
            'category_id' => $this->category->id,
            'name' => 'Stok Tinggi',
            'unit_of_measure' => 'pcs',
            'stock' => 100,
        ]);

        Item::create([
            'division_id' => $this->divisionA->id,
            'category_id' => $this->category->id,
            'name' => 'Stok Rendah',
            'unit_of_measure' => 'pcs',
            'stock' => 10,
        ]);

        $response = $this->actingAs($user)->get('/inventory/stock-monitoring/datatable?stock_max=50');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Stok Rendah');
    });

    it('filter berdasarkan kolom unit_of_measure', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(InventoryPermission::MonitorAllStock->value);

        Item::create([
            'division_id' => $this->divisionA->id,
            'category_id' => $this->category->id,
            'name' => 'Item Box',
            'unit_of_measure' => 'box',
            'stock' => 100,
        ]);

        Item::create([
            'division_id' => $this->divisionA->id,
            'category_id' => $this->category->id,
            'name' => 'Item Pcs',
            'unit_of_measure' => 'pcs',
            'stock' => 200,
        ]);

        $response = $this->actingAs($user)->get('/inventory/stock-monitoring/datatable?unit_of_measure=box');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Item Box');
    });

    it('filter berdasarkan kolom division_id', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(InventoryPermission::MonitorAllStock->value);

        Item::create([
            'division_id' => $this->divisionA->id,
            'category_id' => $this->category->id,
            'name' => 'Item Divisi A',
            'unit_of_measure' => 'pcs',
            'stock' => 100,
        ]);

        Item::create([
            'division_id' => $this->divisionB->id,
            'category_id' => $this->category->id,
            'name' => 'Item Divisi B',
            'unit_of_measure' => 'pcs',
            'stock' => 50,
        ]);

        $response = $this->actingAs($user)->get("/inventory/stock-monitoring/datatable?division_id={$this->divisionA->id}");
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Item Divisi A');
    });

    it('filter division_id ALL mengambil semua divisi', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(InventoryPermission::MonitorAllStock->value);

        Item::create([
            'division_id' => $this->divisionA->id,
            'category_id' => $this->category->id,
            'name' => 'Item A',
            'unit_of_measure' => 'pcs',
            'stock' => 100,
        ]);

        Item::create([
            'division_id' => $this->divisionB->id,
            'category_id' => $this->category->id,
            'name' => 'Item B',
            'unit_of_measure' => 'pcs',
            'stock' => 50,
        ]);

        $response = $this->actingAs($user)->get('/inventory/stock-monitoring/datatable?division_id=ALL');
        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    });
});

// ============================================================================
// 7. Print Excel menghasilkan file excel
// ============================================================================

describe('Print Excel', function () {

    it('print-excel menghasilkan response 200 dengan content-type xlsx', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(InventoryPermission::MonitorAllStock->value);

        Item::create([
            'division_id' => $this->divisionA->id,
            'category_id' => $this->category->id,
            'name' => 'Item Excel',
            'unit_of_measure' => 'pcs',
            'stock' => 100,
        ]);

        $response = $this->actingAs($user)->get('/inventory/stock-monitoring/print-excel');

        $response->assertOk();
        $contentDisposition = $response->headers->get('content-disposition');
        expect($contentDisposition)->toContain('.xlsx');
    });
});

// ============================================================================
// Konversi Stok
// ============================================================================

// (a) Multiplier <= 1: tidak bisa konversi
describe('Konversi: multiplier <= 1 tidak bisa konversi', function () {

    it('item dengan multiplier 1 redirect dengan error saat akses halaman convert', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::ConvertStock->value);
        $user->givePermissionTo(InventoryPermission::MonitorStock->value);

        $item = Item::create([
            'division_id' => $this->divisionA->id,
            'category_id' => $this->category->id,
            'name' => 'Item Satuan',
            'unit_of_measure' => 'pcs',
            'stock' => 100,
            'multiplier' => 1,
        ]);

        $response = $this->actingAs($user)->get("/inventory/stock-monitoring/{$item->id}/convert");

        $response->assertRedirect(route('inventory.stock-monitoring.index'));
    });

    it('item dengan multiplier 1 gagal proses konversi', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::ConvertStock->value);

        $item = Item::create([
            'division_id' => $this->divisionA->id,
            'category_id' => $this->category->id,
            'name' => 'Item Satuan',
            'unit_of_measure' => 'pcs',
            'stock' => 100,
            'multiplier' => 1,
        ]);

        $response = $this->actingAs($user)->post("/inventory/stock-monitoring/{$item->id}/convert", [
            'quantity' => 5,
        ]);

        // Stok tidak berubah
        $this->assertDatabaseHas('items', ['id' => $item->id, 'stock' => 100]);
    });
});

// (b) Multiplier > 1 dengan reference_item_id: konversi normal
describe('Konversi: multiplier > 1 dengan reference_item_id (normal)', function () {

    it('konversi berhasil: sumber berkurang, target bertambah qty * multiplier', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::ConvertStock->value);
        $user->givePermissionTo(InventoryPermission::MonitorStock->value);

        // Item satuan kecil (pcs)
        $pcsItem = Item::create([
            'division_id' => $this->divisionA->id,
            'category_id' => $this->category->id,
            'name' => 'Kertas Lembar',
            'unit_of_measure' => 'lembar',
            'stock' => 0,
            'multiplier' => 1,
        ]);

        // Item satuan besar (rim) → 1 rim = 500 lembar
        $rimItem = Item::create([
            'division_id' => $this->divisionA->id,
            'category_id' => $this->category->id,
            'name' => 'Kertas Rim',
            'unit_of_measure' => 'rim',
            'stock' => 10,
            'multiplier' => 500,
            'reference_item_id' => $pcsItem->id,
        ]);

        $response = $this->actingAs($user)->post("/inventory/stock-monitoring/{$rimItem->id}/convert", [
            'quantity' => 3,
        ]);

        $response->assertRedirect(route('inventory.stock-monitoring.index'));

        // rim: 10 - 3 = 7
        $this->assertDatabaseHas('items', ['id' => $rimItem->id, 'stock' => 7]);
        // lembar: 0 + (3 * 500) = 1500
        $this->assertDatabaseHas('items', ['id' => $pcsItem->id, 'stock' => 1500]);
    });

    it('konversi mencatat item_transaction ConversionOut dan ConversionIn', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::ConvertStock->value);

        $pcsItem = Item::create([
            'division_id' => $this->divisionA->id,
            'category_id' => $this->category->id,
            'name' => 'Pcs Item',
            'unit_of_measure' => 'pcs',
            'stock' => 50,
            'multiplier' => 1,
        ]);

        $boxItem = Item::create([
            'division_id' => $this->divisionA->id,
            'category_id' => $this->category->id,
            'name' => 'Box Item',
            'unit_of_measure' => 'box',
            'stock' => 20,
            'multiplier' => 10,
            'reference_item_id' => $pcsItem->id,
        ]);

        $this->actingAs($user)->post("/inventory/stock-monitoring/{$boxItem->id}/convert", [
            'quantity' => 5,
        ]);

        // ConversionOut: 5 box keluar
        $this->assertDatabaseHas('item_transactions', [
            'item_id' => $boxItem->id,
            'type' => ItemTransactionType::ConversionOut->value,
            'quantity' => 5,
            'user_id' => $user->id,
        ]);

        // ConversionIn: 5 * 10 = 50 pcs masuk
        $this->assertDatabaseHas('item_transactions', [
            'item_id' => $pcsItem->id,
            'type' => ItemTransactionType::ConversionIn->value,
            'quantity' => 50,
            'user_id' => $user->id,
        ]);
    });
});

// (c) Multiplier > 1, reference_item_id null → auto-create dari main_reference
describe('Konversi: multiplier > 1, reference_item_id null (auto-create dari main_reference)', function () {

    it('auto-create item divisi dari main_reference lalu konversi', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::ConvertStock->value);

        // === GUDANG UTAMA ===
        // Item pcs di gudang utama
        $masterPcs = Item::create([
            'category_id' => $this->category->id,
            'name' => 'Tinta Printer (pcs)',
            'unit_of_measure' => 'pcs',
            'stock' => 1000,
            'multiplier' => 1,
        ]);

        // Item box di gudang utama → reference ke pcs
        $masterBox = Item::create([
            'category_id' => $this->category->id,
            'name' => 'Tinta Printer (box)',
            'unit_of_measure' => 'box',
            'stock' => 100,
            'multiplier' => 12,
            'reference_item_id' => $masterPcs->id,
        ]);

        // === DIVISI ===
        // Item box di divisi → TIDAK punya reference_item_id (belum ada pcs di divisi)
        $divisionBox = Item::create([
            'division_id' => $this->divisionA->id,
            'category_id' => $this->category->id,
            'name' => 'Tinta Printer (box)',
            'unit_of_measure' => 'box',
            'stock' => 8,
            'multiplier' => 12,
            'reference_item_id' => null,
            'main_reference_item_id' => $masterBox->id,
        ]);

        $response = $this->actingAs($user)->post("/inventory/stock-monitoring/{$divisionBox->id}/convert", [
            'quantity' => 2,
        ]);

        $response->assertRedirect(route('inventory.stock-monitoring.index'));

        // 1. Item pcs baru harus dibuat di divisi
        $divisionPcs = Item::where('division_id', $this->divisionA->id)
            ->where('main_reference_item_id', $masterPcs->id)
            ->first();

        expect($divisionPcs)->not->toBeNull();
        expect($divisionPcs->name)->toBe('Tinta Printer (pcs)');
        expect($divisionPcs->unit_of_measure)->toBe('pcs');

        // 2. reference_item_id pada divisionBox harus diupdate
        $divisionBox->refresh();
        expect($divisionBox->reference_item_id)->toBe($divisionPcs->id);

        // 3. Stok: box 8 - 2 = 6, pcs 0 + (2 * 12) = 24
        expect($divisionBox->stock)->toBe(6);
        expect($divisionPcs->stock)->toBe(24);

        // 4. Transaksi tercatat
        $this->assertDatabaseHas('item_transactions', [
            'item_id' => $divisionBox->id,
            'type' => ItemTransactionType::ConversionOut->value,
            'quantity' => 2,
        ]);

        $this->assertDatabaseHas('item_transactions', [
            'item_id' => $divisionPcs->id,
            'type' => ItemTransactionType::ConversionIn->value,
            'quantity' => 24,
        ]);
    });
});

// (d) reference_item_id null + main reference reference_item_id null → gagal
describe('Konversi: reference_item_id null dan main_reference tidak punya reference → gagal', function () {

    it('tidak bisa konversi jika main_reference_item juga tidak punya reference_item_id', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::ConvertStock->value);

        // Master box TANPA reference_item_id
        $masterBox = Item::create([
            'category_id' => $this->category->id,
            'name' => 'Item Box Tanpa Referensi',
            'unit_of_measure' => 'box',
            'stock' => 50,
            'multiplier' => 10,
            'reference_item_id' => null,
        ]);

        // Division box → main_reference mengarah ke master yg juga null
        $divisionBox = Item::create([
            'division_id' => $this->divisionA->id,
            'category_id' => $this->category->id,
            'name' => 'Item Box Divisi',
            'unit_of_measure' => 'box',
            'stock' => 20,
            'multiplier' => 10,
            'reference_item_id' => null,
            'main_reference_item_id' => $masterBox->id,
        ]);

        $response = $this->actingAs($user)->post("/inventory/stock-monitoring/{$divisionBox->id}/convert", [
            'quantity' => 5,
        ]);

        // Seharusnya gagal (redirect back dengan error)
        $response->assertRedirect();
        $response->assertSessionHasErrors('quantity');

        // Stok tidak berubah
        $this->assertDatabaseHas('items', ['id' => $divisionBox->id, 'stock' => 20]);
    });

    it('tidak bisa konversi jika tidak punya main_reference_item_id sama sekali', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::ConvertStock->value);

        // Division box tanpa reference apapun
        $divisionBox = Item::create([
            'division_id' => $this->divisionA->id,
            'category_id' => $this->category->id,
            'name' => 'Item Tanpa Referensi',
            'unit_of_measure' => 'box',
            'stock' => 15,
            'multiplier' => 10,
            'reference_item_id' => null,
            'main_reference_item_id' => null,
        ]);

        $response = $this->actingAs($user)->post("/inventory/stock-monitoring/{$divisionBox->id}/convert", [
            'quantity' => 3,
        ]);

        // Seharusnya gagal
        $response->assertRedirect();
        $response->assertSessionHasErrors('quantity');

        // Stok tidak berubah
        $this->assertDatabaseHas('items', ['id' => $divisionBox->id, 'stock' => 15]);
    });
});
