<?php

use App\Models\User;
use Modules\Inventory\Models\CategoryItem;
use Modules\Inventory\Models\Item;
use Modules\Inventory\Models\ItemTransaction;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    // Create permissions
    Permission::firstOrCreate(['name' => \Modules\Inventory\Enums\InventoryPermission::ViewItem->value, 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => \Modules\Inventory\Enums\InventoryPermission::ManageItem->value, 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => \Modules\Inventory\Enums\InventoryPermission::IssueItemGudang->value, 'guard_name' => 'web']);
    
    // Create role with full permissions
    $this->testRole = Role::firstOrCreate(['name' => 'Test Role Items', 'guard_name' => 'web']);
    $this->testRole->syncPermissions([
        \Modules\Inventory\Enums\InventoryPermission::ViewItem->value,
        \Modules\Inventory\Enums\InventoryPermission::ManageItem->value,
        \Modules\Inventory\Enums\InventoryPermission::IssueItemGudang->value,
    ]);
    
    // Create user
    $this->testUser = User::factory()->create();
    $this->testUser->assignRole($this->testRole);
    
    // Create category for items
    $this->category = CategoryItem::factory()->create(['is_active' => true]);
});

// ============================================
// PERMISSION TESTS
// ============================================

describe('Index Access Permission', function () {
    
    /**
     * Memastikan user tanpa izin apapun ditolak akses ke indeks (403).
     */
    it('denies access to index without any permissions', function () {
        // 1. Hapus semua izin dari role
        $this->testRole->syncPermissions([]);
        
        // 2. Validasi penolakan akses (403)
        $response = $this->actingAs($this->testUser)->get('/inventory/items');
        $response->assertForbidden();
    });
    
    /**
     * Memastikan staf dengan izin 'lihat_barang' dapat mengakses indeks.
     */
    it('allows index access with view permission', function () {
        // 1. Berikan izin 'lihat_barang'
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ViewItem->value]);
        
        // 2. Validasi akses OK (200)
        $response = $this->actingAs($this->testUser)->get('/inventory/items');
        $response->assertOk();
    });
    
    /**
     * Memastikan staf dengan izin 'kelola_barang' dapat mengakses indeks.
     */
    it('allows index access with manage permission', function () {
        // 1. Berikan izin 'kelola_barang'
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ManageItem->value]);
        
        // 2. Validasi akses OK
        $response = $this->actingAs($this->testUser)->get('/inventory/items');
        $response->assertOk();
    });
    
    /**
     * Memastikan staf dengan izin 'keluarkan_stok' dapat mengakses indeks.
     */
    it('allows index access with issue stock permission', function () {
        // 1. Berikan izin 'keluarkan_stok'
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::IssueItemGudang->value]);
        
        // 2. Validasi akses OK
        $response = $this->actingAs($this->testUser)->get('/inventory/items');
        $response->assertOk();
    });
});

describe('CRUD Permission', function () {
    
    /**
     * Memastikan user hanya dengan izin lihat tidak bisa masuk ke halaman create.
     */
    it('denies create page access without manage permission', function () {
        // 1. Berikan izin lihat (bukan kelola)
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ViewItem->value]);
        
        // 2. Validasi penolakan akses create
        $response = $this->actingAs($this->testUser)->get('/inventory/items/create');
        $response->assertForbidden();
    });
    
    /**
     * Memastikan staf dengan izin kelola bisa masuk ke halaman create.
     */
    it('allows create page access with manage permission', function () {
        // 1. Berikan izin kelola
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ManageItem->value]);
        
        // 2. Validasi akses create OK
        $response = $this->actingAs($this->testUser)->get('/inventory/items/create');
        $response->assertOk();
    });
    
    /**
     * Memastikan akses edit ditolak jika hanya punya izin lihat.
     */
    it('denies edit page access without manage permission', function () {
        // 1. Persiapan izin lihat & data barang
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ViewItem->value]);
        $item = Item::factory()->create(['category_id' => $this->category->id]);
        
        // 2. Validasi penolakan akses edit
        $response = $this->actingAs($this->testUser)->get("/inventory/items/{$item->id}/edit");
        $response->assertForbidden();
    });
    
    /**
     * Memastikan akses edit diizinkan dengan izin kelola.
     */
    it('allows edit page access with manage permission', function () {
        // 1. Persiapan data dan izin kelola
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ManageItem->value]);
        $item = Item::factory()->create(['category_id' => $this->category->id]);
        
        // 2. Validasi akses edit OK
        $response = $this->actingAs($this->testUser)->get("/inventory/items/{$item->id}/edit");
        $response->assertOk();
    });
    
    /**
     * Memastikan request update ditolak tanpa izin kelola.
     */
    it('denies saving update without manage permission', function () {
        // 1. Persiapan izin lihat dan aksi update
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ViewItem->value]);
        $item = Item::factory()->create(['category_id' => $this->category->id]);
        
        // 2. Validasi penolakan simpan update
        $response = $this->actingAs($this->testUser)->put("/inventory/items/{$item->id}/update", [
            'name' => 'Updated Name',
            'unit_of_measure' => 'pcs',
            'stock' => 10,
        ]);
        $response->assertForbidden();
    });
    
    /**
     * Memastikan penghapusan ditolak tanpa izin kelola.
     */
    it('denies deleting item without manage permission', function () {
        // 1. Persiapan izin lihat dan data
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ViewItem->value]);
        $item = Item::factory()->create(['category_id' => $this->category->id]);
        
        // 2. Validasi penolakan hapus
        $response = $this->actingAs($this->testUser)->delete("/inventory/items/{$item->id}/delete");
        $response->assertForbidden();
    });
    
    /**
     * Memastikan penghapusan barang diizinkan dengan izin kelola.
     */
    it('allows deleting item with manage permission', function () {
        // 1. Persiapan data dan izin kelola
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ManageItem->value]);
        $item = Item::factory()->create(['category_id' => $this->category->id]);
        
        // 2. Aksi hapus
        $response = $this->actingAs($this->testUser)->delete("/inventory/items/{$item->id}/delete");
        
        // 3. Validasi redirect dan data hilang dari DB
        $response->assertRedirect();
        $this->assertDatabaseMissing('items', ['id' => $item->id]);
    });
});

describe('Issue Stock Permission', function () {
    
    /**
     * Memastikan akses ke form pengeluaran stok ditolak tanpa izin 'keluarkan_stok'.
     */
    it('denies issue form access without issue permission', function () {
        // 1. Persiapan data
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ViewItem->value]);
        $item = Item::factory()->create(['category_id' => $this->category->id, 'stock' => 100]);
        
        // 2. Validasi penolakan akses form issue
        $response = $this->actingAs($this->testUser)->get("/inventory/items/{$item->id}/issue");
        $response->assertForbidden();
    });
    
    /**
     * Memastikan akses form pengeluaran stok diizinkan dengan izin yang sesuai.
     */
    it('allows issue form access with issue permission', function () {
        // 1. Persiapan data dan izin issue
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::IssueItemGudang->value]);
        $item = Item::factory()->create(['category_id' => $this->category->id, 'stock' => 100]);
        
        // 2. Validasi akses OK
        $response = $this->actingAs($this->testUser)->get("/inventory/items/{$item->id}/issue");
        $response->assertOk();
    });
    
    /**
     * Memastikan aksi pengeluaran stok ditolak jika tidak memiliki izin.
     */
    it('denies issuing stock without issue permission', function () {
        // 1. Persiapan data tanpa izin issue
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::ViewItem->value]);
        $item = Item::factory()->create(['category_id' => $this->category->id, 'stock' => 100]);
        
        // 2. Aksi issue
        $response = $this->actingAs($this->testUser)->post("/inventory/items/{$item->id}/issue", [
            'quantity' => 10,
            'description' => 'Test issue',
        ]);
        
        // 3. Validasi penolakan
        $response->assertForbidden();
    });
    
    /**
     * Memastikan pengeluaran stok diizinkan dan memproses pengurangan jumlah stok.
     */
    it('allows issuing stock with issue permission', function () {
        // 1. Persiapan data dan izin issue
        $this->testRole->syncPermissions([\Modules\Inventory\Enums\InventoryPermission::IssueItemGudang->value]);
        $item = Item::factory()->create(['category_id' => $this->category->id, 'stock' => 100]);
        
        // 2. Aksi issue stok 10
        $response = $this->actingAs($this->testUser)->post("/inventory/items/{$item->id}/issue", [
            'quantity' => 10,
            'description' => 'Test issue',
        ]);
        
        // 3. Validasi sukses, redirect, dan stok berkurang jadi 90
        $response->assertRedirect();
        $item->refresh();
        expect($item->stock)->toBe(90);
    });
});

// ============================================
// CRUD OPERATIONS
// ============================================

describe('CRUD Operations', function () {
    
    /**
     * Memastikan pembuatan barang baru berhasil dengan data yang valid.
     */
    it('can create item with valid data', function () {
        // 1. Aksi simpan barang baru
        $response = $this->actingAs($this->testUser)->post('/inventory/items/store', [
            'name' => 'Barang Baru',
            'category_id' => $this->category->id,
            'unit_of_measure' => 'pcs',
            'stock' => 50,
            'description' => 'Deskripsi barang baru',
        ]);
        
        // 2. Validasi redirect dan data tersimpan di DB
        $response->assertRedirect();
        $this->assertDatabaseHas('items', [
            'name' => 'Barang Baru',
            'category_id' => $this->category->id,
            'unit_of_measure' => 'pcs',
            'stock' => 50,
        ]);
    });
    
    /**
     * Memastikan data barang dapat diperbarui.
     */
    it('can update existing item', function () {
        // 1. Persiapan data awal
        $item = Item::factory()->create([
            'category_id' => $this->category->id,
            'name' => 'Old Name',
            'stock' => 10,
        ]);
        
        // 2. Aksi update data
        $response = $this->actingAs($this->testUser)->put("/inventory/items/{$item->id}/update", [
            'name' => 'New Name',
            'category_id' => $this->category->id,
            'unit_of_measure' => 'box',
            'stock' => 25,
            'description' => 'Updated description',
        ]);
        
        // 3. Validasi redirect dan perubahan data di DB
        $response->assertRedirect();
        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'name' => 'New Name',
            'stock' => 25,
        ]);
    });
    
    /**
     * Memastikan barang dapat dihapus dari sistem.
     */
    it('can delete item', function () {
        // 1. Persiapan data
        $item = Item::factory()->create(['category_id' => $this->category->id]);
        
        // 2. Aksi hapus
        $response = $this->actingAs($this->testUser)->delete("/inventory/items/{$item->id}/delete");
        
        // 3. Validasi redirect dan data hilang
        $response->assertRedirect();
        $this->assertDatabaseMissing('items', ['id' => $item->id]);
    });
    
    /**
     * Memastikan sistem menerima pembuatan barang dengan stok nol.
     */
    it('can create item with zero stock', function () {
        // 1. Aksi simpan barang stok 0 (diizinkan)
        $response = $this->actingAs($this->testUser)->post('/inventory/items/store', [
            'name' => 'Barang Tanpa Stok',
            'category_id' => $this->category->id,
            'unit_of_measure' => 'pcs',
            'stock' => 0,
        ]);
        
        // 2. Validasi sukses
        $response->assertRedirect();
        $this->assertDatabaseHas('items', [
            'name' => 'Barang Tanpa Stok',
            'stock' => 0,
        ]);
    });
});

// ============================================
// VALIDATION
// ============================================

describe('Validation', function () {
    
    /**
     * Validasi field 'name' wajib diisi.
     */
    it('fails to create item without name (required)', function () {
        // 1. Aksi simpan tanpa nama
        $response = $this->actingAs($this->testUser)->post('/inventory/items/store', [
            'name' => '',
            'unit_of_measure' => 'pcs',
            'stock' => 10,
        ]);
        
        // 2. Validasi adanya error pada field 'name'
        $response->assertSessionHasErrors('name');
    });
    
    /**
     * Validasi field 'unit_of_measure' wajib diisi.
     */
    it('fails to create item without unit of measure (required)', function () {
        // 1. Aksi simpan tanpa satuan (UoM)
        $response = $this->actingAs($this->testUser)->post('/inventory/items/store', [
            'name' => 'Test Item',
            'unit_of_measure' => '',
            'stock' => 10,
        ]);
        
        // 2. Validasi error 'unit_of_measure'
        $response->assertSessionHasErrors('unit_of_measure');
    });
    
    /**
     * Validasi stok tidak boleh bernilai negatif.
     */
    it('fails to create item with negative stock', function () {
        // 1. Aksi simpan stok negatif (-10)
        $response = $this->actingAs($this->testUser)->post('/inventory/items/store', [
            'name' => 'Test Item',
            'unit_of_measure' => 'pcs',
            'stock' => -10,
        ]);
        
        // 2. Validasi error 'stock'
        $response->assertSessionHasErrors('stock');
    });
    
    /**
     * Memastikan barang dapat dibuat tanpa kategori.
     */
    it('can create item without category (nullable)', function () {
        // 1. Aksi simpan tanpa memilih kategori
        $response = $this->actingAs($this->testUser)->post('/inventory/items/store', [
            'name' => 'Barang Tanpa Kategori',
            'unit_of_measure' => 'pcs',
            'stock' => 10,
        ]);
        
        // 2. Validasi sukses tersimpan dengan category_id null
        $response->assertRedirect();
        $this->assertDatabaseHas('items', [
            'name' => 'Barang Tanpa Kategori',
            'category_id' => null,
        ]);
    });
});

// ============================================
// ISSUE STOCK
// ============================================

describe('Issue Stock', function () {
    
    /**
     * Test pengurangan stok dengan jumlah yang diizinkan.
     */
    it('can issue stock with valid quantity', function () {
        // 1. Persiapan barang dengan stok 100
        $item = Item::factory()->create(['category_id' => $this->category->id, 'stock' => 100]);
        
        // 2. Aksi keluarkan 20 stok
        $response = $this->actingAs($this->testUser)->post("/inventory/items/{$item->id}/issue", [
            'quantity' => 20,
            'description' => 'Pengeluaran untuk divisi',
        ]);
        
        // 3. Validasi sisa stok menjadi 80
        $response->assertRedirect();
        $item->refresh();
        expect($item->stock)->toBe(80);
    });
    
    /**
     * Memastikan sistem menolak pengeluaran jika stok tidak mencukupi.
     */
    it('fails to issue stock exceeding available stock', function () {
        // 1. Persiapan stok 50
        $item = Item::factory()->create(['category_id' => $this->category->id, 'stock' => 50]);
        
        // 2. Aksi keluarkan 100 (ilegal)
        $response = $this->actingAs($this->testUser)->post("/inventory/items/{$item->id}/issue", [
            'quantity' => 100,
            'description' => 'Pengeluaran melebihi stok',
        ]);
        
        // 3. Validasi error quantity dan stok tetap 50
        $response->assertSessionHasErrors('quantity');
        $item->refresh();
        expect($item->stock)->toBe(50);
    });
    
    /**
     * Validasi pengeluaran stok minimal 1.
     */
    it('fails to issue stock with zero quantity', function () {
        // 1. Persiapan data
        $item = Item::factory()->create(['category_id' => $this->category->id, 'stock' => 100]);
        
        // 2. Aksi issue quantity 0
        $response = $this->actingAs($this->testUser)->post("/inventory/items/{$item->id}/issue", [
            'quantity' => 0,
            'description' => 'Invalid issue',
        ]);
        $response->assertSessionHasErrors('quantity');
    });
    
    /**
     * Menjamin setiap pengeluaran stok memiliki alasan/deskripsi.
     */
    it('fails to issue stock without description', function () {
        // 1. Persiapan data
        $item = Item::factory()->create(['category_id' => $this->category->id, 'stock' => 100]);
        
        // 2. Aksi issue tanpa memberikan alasan/deskripsi
        $response = $this->actingAs($this->testUser)->post("/inventory/items/{$item->id}/issue", [
            'quantity' => 10,
            'description' => '',
        ]);
        $response->assertSessionHasErrors('description');
    });
    
    /**
     * Memastikan log transaksi terbentuk di database setelah stok dikurangi.
     */
    it('creates transaction record after issuing stock', function () {
        // 1. Persiapan data
        $item = Item::factory()->create(['category_id' => $this->category->id, 'stock' => 100]);
        
        // 2. Aksi issue 25 stok
        $response = $this->actingAs($this->testUser)->post("/inventory/items/{$item->id}/issue", [
            'quantity' => 25,
            'description' => 'Test transaksi',
        ]);
        
        // 3. Validasi adanya record transaksi di tabel 'item_transactions'
        $response->assertRedirect();
        $this->assertDatabaseHas('item_transactions', [
            'item_id' => $item->id,
            'quantity' => 25,
            'user_id' => $this->testUser->id,
        ]);
    });
});

// ============================================
// DATATABLE
// ============================================

describe('Datatable', function () {
    
    /**
     * Tes endpoint datatable mengembalikan data dalam format JSON yang benar.
     */
    it('datatable returns correct data format', function () {
        // 1. Tambah 5 data barang
        Item::factory()->count(5)->create(['category_id' => $this->category->id]);
        
        // 2. Request json datatable
        $response = $this->actingAs($this->testUser)->get('/inventory/items/datatable');
        $response->assertOk();
        
        // 3. Validasi struktur response memiliki key 'data'
        $data = $response->json();
        expect($data)->toHaveKey('data');
        expect(count($data['data']))->toBeGreaterThan(0);
    });
    
    /**
     * Test fitur filter pencarian pada datatable.
     */
    it('search/filter works correctly', function () {
        // 1. Tambah variasi data barang
        Item::factory()->create(['category_id' => $this->category->id, 'name' => 'Laptop Dell']);
        Item::factory()->create(['category_id' => $this->category->id, 'name' => 'Mouse Logitech']);
        Item::factory()->create(['category_id' => $this->category->id, 'name' => 'Keyboard Mechanical']);
        
        // 2. Cari berdasarkan kata kunci "Laptop"
        $response = $this->actingAs($this->testUser)->get('/inventory/items/datatable?search=Laptop');
        $response->assertOk();
        
        // 3. Validasi bahwa hasil pencarian mengandung "Laptop"
        $data = $response->json();
        expect($data)->toHaveKey('data');
        expect(count($data['data']))->toBeGreaterThan(0);
        
        $found = false;
        foreach ($data['data'] as $item) {
            if (str_contains($item['name'], 'Laptop')) {
                $found = true;
                break;
            }
        }
        expect($found)->toBeTrue();
    });
});

// ============================================
// EXPORT EXCEL
// ============================================

describe('Export Excel', function () {
    
    /**
     * Memastikan hasil ekspor mengembalikan file tipe Excel (.xlsx).
     */
    it('export returns valid Excel file', function () {
        // 1. Tambah data barang
        Item::factory()->count(5)->create(['category_id' => $this->category->id]);
        
        // 2. Request print excel
        $response = $this->actingAs($this->testUser)->get('/inventory/items/print-excel');
        
        // 3. Validasi response adalah file Excel (.xlsx)
        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    });
});
