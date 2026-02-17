<?php

use App\Models\Division;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Inventory\Enums\InventoryPermission;
use Modules\Inventory\Enums\StockOpnameStatus;
use Modules\Inventory\Models\CategoryItem;
use Modules\Inventory\Models\Item;
use Modules\Inventory\Models\StockOpname;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create permissions
    foreach (InventoryPermission::values() as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }

    $this->division = Division::factory()->create(['name' => 'Divisi Test', 'is_active' => true]);
    $this->otherDivision = Division::factory()->create(['name' => 'Divisi Lain', 'is_active' => true]);
    
    $this->user = User::factory()->create(['division_id' => $this->division->id]);
    $this->admin = User::factory()->create();
    
    $this->category = CategoryItem::factory()->create();
    $this->item = Item::factory()->create([
        'category_id' => $this->category->id,
        'division_id' => null,
        'stock' => 100
    ]);
    
    $this->divItem = Item::factory()->create([
        'category_id' => $this->category->id,
        'division_id' => $this->division->id,
        'stock' => 50
    ]);
});

// 1. View Warehouse Permission Access (Req 1)
it('mengizinkan ViewWarehouseStockOpname mengakses index, datatable, dan print warehouse', function () {
    $this->user->givePermissionTo(InventoryPermission::ViewWarehouseStockOpname->value);
    
    $this->actingAs($this->user)->get('/inventory/stock-opname/warehouse')->assertOk();
    $this->actingAs($this->user)->get('/inventory/stock-opname/datatable/warehouse')->assertOk();
    $this->actingAs($this->user)->get('/inventory/stock-opname/print-excel/warehouse')->assertOk();
});

// 2. Warehouse Datatable Filter division_id null (Req 2)
it('memastikan datatable warehouse hanya mengirimkan data dengan division_id null', function () {
    $this->user->givePermissionTo(InventoryPermission::ViewWarehouseStockOpname->value);
    
    StockOpname::create(['user_id' => $this->user->id, 'division_id' => null, 'opname_date' => now(), 'status' => StockOpnameStatus::Pending]);
    StockOpname::create(['user_id' => $this->user->id, 'division_id' => $this->division->id, 'opname_date' => now(), 'status' => StockOpnameStatus::Pending]);

    $response = $this->actingAs($this->user)->get('/inventory/stock-opname/datatable/warehouse');
    
    $response->assertOk();
    $data = $response->json('data');
    expect(count($data))->toBe(1);
    expect($data[0]['division'])->toBeNull();
});

// 3. View Division Permission Access (Req 3)
it('mengizinkan ViewDivisionStockOpname mengakses index, datatable, dan print division', function () {
    $this->user->givePermissionTo(InventoryPermission::ViewDivisionStockOpname->value);
    
    $this->actingAs($this->user)->get('/inventory/stock-opname/division')->assertOk();
    $this->actingAs($this->user)->get('/inventory/stock-opname/datatable/division')->assertOk();
    $this->actingAs($this->user)->get('/inventory/stock-opname/print-excel/division')->assertOk();
});

// 4. Division Datatable matching logged user (Req 4)
it('memastikan datatable division hanya mengirimkan data divisi user yang login', function () {
    $this->user->givePermissionTo(InventoryPermission::ViewDivisionStockOpname->value);
    
    StockOpname::create(['user_id' => $this->user->id, 'division_id' => $this->division->id, 'opname_date' => now(), 'status' => StockOpnameStatus::Pending]);
    StockOpname::create(['user_id' => $this->user->id, 'division_id' => $this->otherDivision->id, 'opname_date' => now(), 'status' => StockOpnameStatus::Pending]);

    $response = $this->actingAs($this->user)->get('/inventory/stock-opname/datatable/division');
    
    $response->assertOk();
    $data = $response->json('data');
    expect(count($data))->toBe(1);
    expect($data[0]['division'])->toBe($this->division->name);
});

// 5 & 6. View All Access & Filter (Req 5, 6)
it('mengizinkan ViewAllStockOpname mengakses /all dan melihat semua data tanpa filter divisi', function () {
    $this->admin->givePermissionTo(InventoryPermission::ViewAllStockOpname->value);
    
    StockOpname::create(['user_id' => $this->user->id, 'division_id' => null, 'opname_date' => now(), 'status' => StockOpnameStatus::Pending]);
    StockOpname::create(['user_id' => $this->user->id, 'division_id' => $this->division->id, 'opname_date' => now(), 'status' => StockOpnameStatus::Pending]);

    $this->actingAs($this->admin)->get('/inventory/stock-opname/all')->assertOk();
    $response = $this->actingAs($this->admin)->get('/inventory/stock-opname/datatable/all');
    
    $response->assertOk();
    $data = $response->json('data');
    expect(count($data))->toBe(2);
});

// 7 & 8. Create & Store Division (Req 7, 8)
it('mengizinkan Tambah Stock Opname Divisi mengakses create dan menyimpan dengan division_id sesuai', function () {
    $this->user->givePermissionTo(InventoryPermission::CreateStockOpname->value);
    
    $this->actingAs($this->user)->get('/inventory/stock-opname/division/create')->assertOk();
    
    $response = $this->actingAs($this->user)->post('/inventory/stock-opname/division/store', [
        'division_id' => $this->division->id,
        'opname_date' => now()->format('Y-m-d'),
        'notes' => 'Test Divisi'
    ]);
    
    $response->assertRedirect();
    $this->assertDatabaseHas('stock_opnames', [
        'division_id' => $this->division->id,
        'status' => StockOpnameStatus::Pending->value
    ]);
});

// 9 & 10. Create & Store Warehouse (Req 9, 10)
it('mengizinkan Tambah Stock Opname Gudang menyimpan dengan division_id null', function () {
    $this->user->givePermissionTo(InventoryPermission::CreateStockOpname->value);
    
    $response = $this->actingAs($this->user)->post('/inventory/stock-opname/warehouse/store', [
        'division_id' => 'warehouse',
        'opname_date' => now()->format('Y-m-d'),
        'notes' => 'Test Gudang'
    ]);
    
    $response->assertRedirect();
    $this->assertDatabaseHas('stock_opnames', [
        'division_id' => null,
        'status' => StockOpnameStatus::Pending->value
    ]);
});

// 11. Process Access (Req 11)
it('mengizinkan Process Stock Opname mengakses halaman proses', function () {
    $this->user->givePermissionTo(InventoryPermission::ProcessStockOpname->value);
    $opname = StockOpname::create(['user_id' => $this->user->id, 'division_id' => $this->division->id, 'opname_date' => now(), 'status' => StockOpnameStatus::Pending]);

    $this->actingAs($this->user)->get("/inventory/stock-opname/division/{$opname->id}/process")->assertOk();
});

// 12 - 16. Concurrency Logic (Req 12, 13, 14, 15, 16)
describe('Logika Konkurensi Store (Req 12-16)', function () {
    it('tidak bisa tambah opname gudang jika ada opname gudang yang aktif (Req 12)', function () {
        StockOpname::create(['user_id' => $this->user->id, 'division_id' => null, 'opname_date' => now(), 'status' => StockOpnameStatus::Pending]);
        $this->user->givePermissionTo(InventoryPermission::CreateStockOpname->value);

        $response = $this->actingAs($this->user)->post('/inventory/stock-opname/warehouse/store', [
            'division_id' => 'warehouse',
            'opname_date' => now()->format('Y-m-d')
        ]);
        $response->assertSessionHas('error', 'Masih ada Stock Opname yang belum selesai.');
    });

    it('tidak bisa tambah opname divisi jika divisi tersebut punya opname aktif (Req 13, 16)', function () {
        StockOpname::create(['user_id' => $this->user->id, 'division_id' => $this->division->id, 'opname_date' => now(), 'status' => StockOpnameStatus::Proses]);
        $this->user->givePermissionTo(InventoryPermission::CreateStockOpname->value);

        $response = $this->actingAs($this->user)->post('/inventory/stock-opname/division/store', [
            'division_id' => $this->division->id,
            'opname_date' => now()->format('Y-m-d')
        ]);
        $response->assertSessionHas('error', 'Masih ada Stock Opname yang belum selesai.');
    });

    it('bisa tambah opname divisi B jika divisi A sedang opname (Req 15)', function () {
        StockOpname::create(['user_id' => $this->user->id, 'division_id' => $this->division->id, 'opname_date' => now(), 'status' => StockOpnameStatus::Pending]);
        
        $userB = User::factory()->create(['division_id' => $this->otherDivision->id]);
        $userB->givePermissionTo(InventoryPermission::CreateStockOpname->value);

        $response = $this->actingAs($userB)->post('/inventory/stock-opname/division/store', [
            'division_id' => $this->otherDivision->id,
            'opname_date' => now()->format('Y-m-d')
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('stock_opnames', ['division_id' => $this->otherDivision->id]);
    });
});

// 17. Draft Process (Req 17)
it('menyimpan data sebagai draf dan mengubah status menjadi Proses (Req 17)', function () {
    $this->user->givePermissionTo(InventoryPermission::ProcessStockOpname->value);
    $opname = StockOpname::create(['user_id' => $this->user->id, 'division_id' => null, 'opname_date' => now(), 'status' => StockOpnameStatus::Pending]);

    $response = $this->actingAs($this->user)->post("/inventory/stock-opname/warehouse/{$opname->id}/process", [
        'items' => [
            ['item_id' => $this->item->id, 'physical_stock' => 90, 'notes' => 'Draft note']
        ],
        'confirm' => false
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('stock_opnames', ['id' => $opname->id, 'status' => StockOpnameStatus::Proses->value]);
    $this->assertDatabaseHas('stock_opname_items', ['stock_opname_id' => $opname->id, 'physical_stock' => 90, 'notes' => 'Draft note']);
});

// 18. Confirm Process (Req 18)
it('menyimpan sebagai konfirmasi, status Stock Opname, dan update stok item (Req 18)', function () {
    $this->user->givePermissionTo(InventoryPermission::ProcessStockOpname->value);
    $opname = StockOpname::create(['user_id' => $this->user->id, 'division_id' => null, 'opname_date' => now(), 'status' => StockOpnameStatus::Pending]);

    $response = $this->actingAs($this->user)->post("/inventory/stock-opname/warehouse/{$opname->id}/process", [
        'items' => [
            ['item_id' => $this->item->id, 'physical_stock' => 80]
        ],
        'confirm' => true
    ]);

    $response->assertRedirect();
    $opname->refresh();
    expect($opname->status)->toBe(StockOpnameStatus::StockOpname);
    
    $this->item->refresh();
    expect((float) $this->item->stock)->toBe(80.0);
    
    $this->assertDatabaseHas('item_transactions', [
        'item_id' => $this->item->id,
        'quantity' => 20
    ]);
});

// 19 & 20. Finalize Access & Date Validation (Req 19, 20)
it('valdating finalize date h+1 to h+5 (Req 20)', function () {
    $this->user->givePermissionTo(InventoryPermission::FinalizeStockOpname->value);
    
    // Opname today -> cannot finalize today (Req 20)
    $opnameToday = StockOpname::create([
        'user_id' => $this->user->id, 
        'division_id' => null, 
        'opname_date' => now()->format('Y-m-d'), 
        'confirmed_at' => now(),
        'status' => StockOpnameStatus::StockOpname
    ]);
    $this->actingAs($this->user)->get("/inventory/stock-opname/warehouse/{$opnameToday->id}/finalize")
        ->assertStatus(302); // Exception caught by controller back()

    // Opname yesterday -> can finalize
    $opnameYesterday = StockOpname::create([
        'user_id' => $this->user->id, 
        'division_id' => null, 
        'opname_date' => now()->subDay()->format('Y-m-d'), 
        'confirmed_at' => now()->subDay(),
        'status' => StockOpnameStatus::StockOpname
    ]);
    $this->actingAs($this->user)->get("/inventory/stock-opname/warehouse/{$opnameYesterday->id}/finalize")
        ->assertOk();
});

// 21. Finalize Execution (Req 21)
it('menyelesaikan finalisasi dan update stok final (Req 21)', function () {
    $this->user->givePermissionTo(InventoryPermission::FinalizeStockOpname->value);
    $opname = StockOpname::create([
        'user_id' => $this->user->id, 
        'division_id' => null, 
        'opname_date' => now()->subDay()->format('Y-m-d'), 
        'confirmed_at' => now()->subDay(),
        'status' => StockOpnameStatus::StockOpname
    ]);
    $opname->items()->create(['item_id' => $this->item->id, 'system_stock' => 100, 'physical_stock' => 80]);
    $this->item->update(['stock' => 80]);

    $response = $this->actingAs($this->user)->post("/inventory/stock-opname/warehouse/{$opname->id}/finalize", [
        'items' => [
            ['item_id' => $this->item->id, 'final_stock' => 85, 'final_notes' => 'Adjusted']
        ]
    ]);

    $response->assertRedirect();
    $opname->refresh();
    expect($opname->status)->toBe(StockOpnameStatus::Finish);
    
    $this->item->refresh();
    expect((float) $this->item->stock)->toBe(85.0);
});

// 22 & 23. Datatable Search (Req 22, 23)
it('memastikan search global dan kolom pada datatable berfungsi (Req 22, 23)', function () {
    $this->user->givePermissionTo(InventoryPermission::ViewAllStockOpname->value);
    StockOpname::create(['user_id' => $this->user->id, 'division_id' => null, 'opname_date' => now(), 'status' => StockOpnameStatus::Pending, 'notes' => 'UniqueNote']);

    // Global search
    $response = $this->actingAs($this->user)->get('/inventory/stock-opname/datatable/all?search=UniqueNote');
    expect(count($response->json('data')))->toBe(1);

    // Column search (status) (Req 23)
    $response = $this->actingAs($this->user)->get('/inventory/stock-opname/datatable/all?status=Pending');
    expect(count($response->json('data')))->toBe(1);
    
    // Column search (division_id) (Req 23)
    $response = $this->actingAs($this->user)->get('/inventory/stock-opname/datatable/all?division_id=MAIN_WAREHOUSE');
    expect(count($response->json('data')))->toBe(1);

    // Column search (user) (Req 23)
    $response = $this->actingAs($this->user)->get('/inventory/stock-opname/datatable/all?user=' . $this->user->name);
    expect(count($response->json('data')))->toBe(1);

    // Pagination/Limit (Req 22)
    StockOpname::factory()->count(25)->create(['user_id' => $this->user->id, 'division_id' => null, 'opname_date' => now()]);
    $response = $this->actingAs($this->user)->get('/inventory/stock-opname/datatable/all?limit=10');
    expect(count($response->json('data')))->toBe(10);
    expect($response->json('total'))->toBe(26); // 1 + 25
});

// 24. Print Excel (Req 24)
it('memastikan print menghasilkan file excel (Req 24)', function () {
    $this->user->givePermissionTo(InventoryPermission::ViewWarehouseStockOpname->value);
    $response = $this->actingAs($this->user)->get('/inventory/stock-opname/print-excel/warehouse');
    
    $response->assertOk();
    $response->assertHeader('Content-Disposition', 'attachment; filename="Laporan Stock Opname Gudang Per '.date('d F Y').'.xlsx"');
});
