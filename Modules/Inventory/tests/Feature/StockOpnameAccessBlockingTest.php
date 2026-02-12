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

uses(RefreshDatabase::class);

beforeEach(function () {
    // Register permissions
    $permissions = InventoryPermission::values();
    foreach ($permissions as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }

    $this->divisionA = Division::factory()->create(['name' => 'Divisi A']);
    $this->divisionB = Division::factory()->create(['name' => 'Divisi B']);

    $this->userA = User::factory()->create(['division_id' => $this->divisionA->id]);
    $this->userB = User::factory()->create(['division_id' => $this->divisionB->id]);

    // Give some basic permissions
    $this->userA->givePermissionTo([
        InventoryPermission::ViewCategory->value,
        InventoryPermission::ViewItem->value,
        InventoryPermission::ViewWarehouseOrderDivisi->value,
        InventoryPermission::MonitorStock->value,
        InventoryPermission::MonitorItemTransaction->value,
    ]);

    $this->userB->givePermissionTo([
        InventoryPermission::ViewCategory->value,
        InventoryPermission::ViewItem->value,
        InventoryPermission::ViewWarehouseOrderDivisi->value,
        InventoryPermission::MonitorStock->value,
        InventoryPermission::MonitorItemTransaction->value,
    ]);

    $this->category = CategoryItem::factory()->create();
    $this->item = Item::factory()->create(['category_id' => $this->category->id, 'division_id' => null]);
});

describe('Akses Normal (Tanpa Stock Opname Aktif)', function () {
    it('mengizinkan akses ke kategori, item, dan order', function () {
        $this->actingAs($this->userA)->get('/inventory/categories')
            ->assertOk();
        $this->actingAs($this->userA)->get('/inventory/items')
            ->assertOk();
        $this->actingAs($this->userA)->get('/inventory/warehouse-orders')
            ->assertOk();
        $this->actingAs($this->userA)->get('/inventory/stock-monitoring')
            ->assertOk();
    });

    it('mengizinkan akses ke monitoring transaksi (tetap terbuka)', function () {
        $this->actingAs($this->userA)->get('/inventory/transactions')
            ->assertOk();
    });
});

describe('Blocking - Stock Opname Gudang Aktif', function () {
    beforeEach(function () {
        StockOpname::create([
            'user_id' => $this->userA->id,
            'division_id' => null,
            'opname_date' => now(),
            'status' => StockOpnameStatus::Pending,
        ]);
    });

    it('memblokir SEMUA user dari kategori, item, dan order', function () {
        // User A blocked
        $this->actingAs($this->userA)->get('/inventory/categories')->assertStatus(302); // Redirect back (back() in middleware)
        $this->actingAs($this->userA)->get('/inventory/items')->assertStatus(302);
        
        // User B also blocked because Warehouse Opname affects everyone
        $this->actingAs($this->userB)->get('/inventory/categories')->assertStatus(302);
    });

    it('TIDAK memblokir monitoring transaksi', function () {
        $this->actingAs($this->userA)->get('/inventory/transactions')->assertOk();
        $this->actingAs($this->userB)->get('/inventory/transactions')->assertOk();
    });
});

describe('Blocking - Stock Opname Divisi Aktif', function () {
    beforeEach(function () {
        StockOpname::create([
            'user_id' => $this->userA->id,
            'division_id' => $this->divisionA->id,
            'opname_date' => now(),
            'status' => StockOpnameStatus::Proses,
        ]);
    });

    it('memblokir User Divisi A dari menu relevan', function () {
        $this->actingAs($this->userA)->get('/inventory/categories')->assertStatus(302);
        $this->actingAs($this->userA)->get('/inventory/warehouse-orders')->assertStatus(302);
    });

    it('TIDAK memblokir User Divisi B', function () {
        $this->actingAs($this->userB)->get('/inventory/categories')->assertOk();
        $this->actingAs($this->userB)->get('/inventory/warehouse-orders')->assertOk();
    });
});
