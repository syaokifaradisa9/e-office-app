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

describe('Page Access (Permission: MonitorStock / MonitorAllStock)', function () {

    /**
     * Memastikan user dengan izin 'monitor_stok' dapat mengakses halaman index monitoring.
     */
    it('allows user with MonitorStock permission to access index page', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::MonitorStock->value);

        $this->actingAs($user)->get('/inventory/stock-monitoring')
            ->assertOk();
    });

    /**
     * Memastikan user dengan izin 'monitor_semua_stok' dapat mengakses halaman index monitoring.
     */
    it('allows user with MonitorAllStock permission to access index page', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(InventoryPermission::MonitorAllStock->value);

        $this->actingAs($user)->get('/inventory/stock-monitoring')
            ->assertOk();
    });

    /**
     * Memastikan user tanpa izin apapun ditolak saat mengakses halaman index monitoring.
     */
    it('denies user without permission from accessing index page', function () {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/inventory/stock-monitoring')
            ->assertForbidden();
    });

    /**
     * Memastikan user dengan izin 'monitor_stok' dapat mengunduh laporan excel monitoring.
     */
    it('allows user with MonitorStock permission to access print-excel', function () {
        $user = User::factory()->create(['division_id' => $this->divisionA->id]);
        $user->givePermissionTo(InventoryPermission::MonitorStock->value);

        $this->actingAs($user)->get('/inventory/stock-monitoring/print-excel')
            ->assertOk();
    });

    /**
     * Memastikan user dengan izin 'monitor_semua_stok' dapat mengunduh laporan excel monitoring.
     */
    it('allows user with MonitorAllStock permission to access print-excel', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(InventoryPermission::MonitorAllStock->value);

        $this->actingAs($user)->get('/inventory/stock-monitoring/print-excel')
            ->assertOk();
    });

    /**
     * Memastikan user tanpa izin ditolak saat mencoba mengunduh laporan excel monitoring.
     */
    it('denies user without permission from accessing print-excel', function () {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/inventory/stock-monitoring/print-excel')
            ->assertForbidden();
    });

    /**
     * Memastikan user tanpa izin ditolak saat mengakses API datatable monitoring.
     */
    it('denies user without permission from accessing datatable', function () {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/inventory/stock-monitoring/datatable')
            ->assertForbidden();
    });
});

// ============================================================================
// 2. Datatable: MonitorStock filter division_id = user login
// ============================================================================

describe('Datatable Filter Division (MonitorStock)', function () {

    /**
     * Memastikan scoping data: user divisi hanya boleh melihat stok barang dari divisinya sendiri.
     */
    it('only shows own division items for users with MonitorStock', function () {
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

    /**
     * Memastikan user dengan izin global dapat melihat seluruh stok divisi tanpa filter.
     */
    it('shows all division items for users with MonitorAllStock', function () {
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

    /**
     * Memastikan user dengan izin global tetap bisa melihat stok divisi lain biarpun user tsb punya divisi sendiri.
     */
    it('allows MonitorAllStock users to see items from other divisions', function () {
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

    /**
     * Memastikan user dengan izin 'pengeluaran_stok' dapat mengakses form pengeluaran barang.
     */
    it('allows users with IssueStock permission to access issue page', function () {
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

    /**
     * Memastikan user tanpa izin 'pengeluaran_stok' ditolak saat mengakses form pengeluaran barang.
     */
    it('denies users without IssueStock permission from accessing issue page', function () {
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

    /**
     * Menguji proses pengeluaran stok: jumlah stok berkurang dan transaksi tercatat.
     */
    it('reduces stock and records item_transaction on issue', function () {
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

describe('Datatable: Pagination, Limit, and Global Search', function () {

    /**
     * Memastikan fitur pagination pada datatable monitoring stok bekerja dengan benar.
     */
    it('handles pagination correctly', function () {
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

    /**
     * Memastikan pengaturan limit (jumlah data per halaman) pada datatable monitoring stok efektif.
     */
    it('handles limit parameter correctly', function () {
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

    /**
     * Memastikan pencarian global pada datatable dapat menemukan barang berdasarkan nama.
     */
    it('performs global search by item name', function () {
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

    /**
     * Memastikan pencarian global juga mencakup unit satuan barang (UOM).
     */
    it('performs global search by unit_of_measure', function () {
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

    /**
     * Memastikan filter pencarian spesifik pada kolom nama barang bekerja.
     */
    it('filters datatable by name column', function () {
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

    /**
     * Memastikan filter pencarian berdasarkan ID kategori barang bekerja.
     */
    it('filters datatable by category_id column', function () {
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

    /**
     * Memastikan opsi filter 'ALL' pada kategori mengembalikan barang dari seluruh kategori.
     */
    it('returns all categories when category_id filter is set to ALL', function () {
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

    /**
     * Memastikan filter pencarian berdasarkan jumlah stok (minimum) bekerja.
     */
    it('filters datatable by minimum stock value', function () {
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

    /**
     * Memastikan filter pencarian berdasarkan jumlah stok (maksimum) bekerja.
     */
    it('filters datatable by maximum stock value', function () {
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

    /**
     * Memastikan filter pencarian berdasarkan unit satuan barang (UOM) bekerja.
     */
    it('filters datatable by unit_of_measure column', function () {
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

    /**
     * Memastikan filter pencarian berdasarkan ID divisi pemilik barang bekerja.
     */
    it('filters datatable by division_id column', function () {
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

    /**
     * Memastikan opsi filter 'ALL' pada divisi mengembalikan barang dari seluruh divisi.
     */
    it('returns all divisions when division_id filter is set to ALL', function () {
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

    /**
     * Memastikan request laporan excel menghasilkan file dengan mime type yang sesuai (.xlsx).
     */
    it('generates a valid Excel file response', function () {
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
describe('Stock Conversion: Multiplier Validation', function () {

    /**
     * Memastikan barang dengan multiplier 1 (satuan terkecil) tidak diperbolehkan masuk ke halaman konversi.
     */
    it('redirects with error when accessing conversion page for item with multiplier 1', function () {
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

    /**
     * Memastikan sistem menolak proses konversi untuk barang yang memiliki multiplier 1.
     */
    it('fails to process conversion for item with multiplier 1', function () {
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
describe('Stock Conversion: Normal Use Case (Multiplier > 1)', function () {

    /**
     * Menguji keberhasilan konversi: stok satuan besar berkurang, stok satuan kecil bertambah (qty * multiplier).
     */
    it('successfully converts stock: source decreases, target increases proportionately', function () {
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

    /**
     * Memastikan setiap proses konversi mencatat transaksi ConversionOut (keluar) dan ConversionIn (masuk).
     */
    it('records ConversionOut and ConversionIn item_transactions', function () {
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

describe('Stock Conversion: Auto-Create Division Item', function () {

    /**
     * Menguji skenario dimana item target (satuan kecil) belum ada di divisi, sistem harus otomatis membuatnya.
     */
    it('auto-creates division item from main_reference if missing before conversion', function () {
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
