<?php

namespace Tests\Unit\Inventory;

use App\Models\Division;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Inventory\Enums\InventoryPermission;
use Modules\Inventory\Models\CategoryItem;
use Modules\Inventory\Models\Item;
use Modules\Inventory\Models\ItemTransaction;
use Modules\Inventory\Models\StockOpname;
use Modules\Inventory\Services\StockOpnameService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new StockOpnameService();
    
    // Create permissions
    Permission::firstOrCreate(['name' => InventoryPermission::ManageWarehouseStockOpname->value, 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => InventoryPermission::ManageDivisionStockOpname->value, 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => InventoryPermission::ViewWarehouseStockOpname->value, 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => InventoryPermission::ViewDivisionStockOpname->value, 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => InventoryPermission::ViewAllStockOpname->value, 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => InventoryPermission::ConfirmStockOpname->value, 'guard_name' => 'web']);
    
    // Create division
    $this->division = Division::factory()->create(['is_active' => true]);
    
    // Create category and items
    $this->category = CategoryItem::factory()->create();
    $this->warehouseItem = Item::factory()->create([
        'category_id' => $this->category->id,
        'division_id' => null, // Warehouse item
        'stock' => 100,
    ]);
    $this->divisionItem = Item::factory()->create([
        'category_id' => $this->category->id,
        'division_id' => $this->division->id,
        'stock' => 50,
    ]);
});

// ============================================
// getItemsForOpname
// ============================================

describe('getItemsForOpname', function () {
    
    it('mengembalikan item gudang untuk type warehouse', function () {
        $user = User::factory()->create();
        
        $items = $this->service->getItemsForOpname($user, 'warehouse');
        
        expect($items->count())->toBeGreaterThan(0);
        expect($items->first()->division_id)->toBeNull();
    });
    
    it('mengembalikan item divisi user untuk type division', function () {
        $user = User::factory()->create(['division_id' => $this->division->id]);
        
        $items = $this->service->getItemsForOpname($user, 'division');
        
        // Query only returns items for user's division
        // Note: division_id is not in selected columns, only id, name, stock, unit_of_measure
        expect($items->count())->toBeGreaterThan(0);
    });
    
    it('mengembalikan kosong jika user tidak punya divisi', function () {
        $user = User::factory()->create(['division_id' => null]);
        
        $items = $this->service->getItemsForOpname($user, 'division');
        
        expect($items->count())->toBe(0);
    });
});

// ============================================
// createWarehouse
// ============================================

describe('createWarehouse', function () {
    
    it('dapat membuat stock opname gudang dengan data valid', function () {
        $user = User::factory()->create();
        
        $data = [
            'opname_date' => now()->format('Y-m-d'),
            'notes' => 'Test opname gudang',
            'items' => [
                [
                    'item_id' => $this->warehouseItem->id,
                    'physical_stock' => 95,
                    'notes' => 'Selisih 5',
                ],
            ],
        ];
        
        $opname = $this->service->createWarehouse($data, $user);
        
        expect($opname)->toBeInstanceOf(StockOpname::class);
        expect($opname->user_id)->toBe($user->id);
        expect($opname->division_id)->toBeNull();
        expect($opname->status)->toBe('Draft');
    });
    
    it('menyimpan items dengan difference yang benar', function () {
        $user = User::factory()->create();
        
        $data = [
            'opname_date' => now()->format('Y-m-d'),
            'items' => [
                [
                    'item_id' => $this->warehouseItem->id,
                    'physical_stock' => 90, // System = 100, physical = 90, diff = -10
                ],
            ],
        ];
        
        $opname = $this->service->createWarehouse($data, $user);
        $opnameItem = $opname->items->first();
        
        expect($opnameItem->system_stock)->toBe(100);
        expect($opnameItem->physical_stock)->toBe(90);
        expect($opnameItem->difference)->toBe(-10);
    });
    
    it('status awal adalah Draft', function () {
        $user = User::factory()->create();
        
        $data = [
            'opname_date' => now()->format('Y-m-d'),
            'items' => [
                ['item_id' => $this->warehouseItem->id, 'physical_stock' => 100],
            ],
        ];
        
        $opname = $this->service->createWarehouse($data, $user);
        
        expect($opname->status)->toBe('Draft');
    });
});

// ============================================
// createDivision
// ============================================

describe('createDivision', function () {
    
    it('dapat membuat stock opname divisi dengan data valid', function () {
        $user = User::factory()->create(['division_id' => $this->division->id]);
        
        $data = [
            'opname_date' => now()->format('Y-m-d'),
            'notes' => 'Test opname divisi',
            'items' => [
                [
                    'item_id' => $this->divisionItem->id,
                    'physical_stock' => 45,
                ],
            ],
        ];
        
        $opname = $this->service->createDivision($data, $user);
        
        expect($opname->user_id)->toBe($user->id);
        expect($opname->division_id)->toBe($this->division->id);
        expect($opname->status)->toBe('Draft');
    });
});

// ============================================
// update
// ============================================

describe('update', function () {
    
    it('dapat mengupdate opname yang masih Draft', function () {
        $role = Role::firstOrCreate(['name' => 'Warehouse Manager', 'guard_name' => 'web']);
        $role->syncPermissions([InventoryPermission::ManageWarehouseStockOpname->value]);
        
        $user = User::factory()->create();
        $user->assignRole($role);
        
        // Create opname first
        $opname = StockOpname::create([
            'user_id' => $user->id,
            'division_id' => null,
            'opname_date' => now()->subDay(),
            'status' => 'Draft',
        ]);
        $opname->items()->create([
            'item_id' => $this->warehouseItem->id,
            'system_stock' => 100,
            'physical_stock' => 95,
            'difference' => -5,
        ]);
        
        // Update data
        $data = [
            'opname_date' => now()->format('Y-m-d'),
            'notes' => 'Updated notes',
            'items' => [
                ['item_id' => $this->warehouseItem->id, 'physical_stock' => 90],
            ],
        ];
        
        $updated = $this->service->update($opname, $data, $user);
        
        expect($updated->notes)->toBe('Updated notes');
    });
    
    it('gagal update jika bukan creator', function () {
        $role = Role::firstOrCreate(['name' => 'Warehouse Manager', 'guard_name' => 'web']);
        $role->syncPermissions([InventoryPermission::ManageWarehouseStockOpname->value]);
        
        $creator = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherUser->assignRole($role);
        
        $opname = StockOpname::create([
            'user_id' => $creator->id,
            'division_id' => null,
            'opname_date' => now(),
            'status' => 'Draft',
        ]);
        
        $data = [
            'opname_date' => now()->format('Y-m-d'),
            'items' => [['item_id' => $this->warehouseItem->id, 'physical_stock' => 90]],
        ];
        
        expect(fn () => $this->service->update($opname, $data, $otherUser))
            ->toThrow(\Exception::class, 'Unauthorized');
    });
});

// ============================================
// delete
// ============================================

describe('delete', function () {
    
    it('dapat menghapus opname yang masih Draft', function () {
        $role = Role::firstOrCreate(['name' => 'Warehouse Manager', 'guard_name' => 'web']);
        $role->syncPermissions([InventoryPermission::ManageWarehouseStockOpname->value]);
        
        $user = User::factory()->create();
        $user->assignRole($role);
        
        $opname = StockOpname::create([
            'user_id' => $user->id,
            'division_id' => null,
            'opname_date' => now(),
            'status' => 'Draft',
        ]);
        
        $result = $this->service->delete($opname, $user);
        
        expect($result)->toBeTrue();
        expect(StockOpname::find($opname->id))->toBeNull();
    });
    
    it('gagal hapus jika bukan creator', function () {
        $role = Role::firstOrCreate(['name' => 'Warehouse Manager', 'guard_name' => 'web']);
        $role->syncPermissions([InventoryPermission::ManageWarehouseStockOpname->value]);
        
        $creator = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherUser->assignRole($role);
        
        $opname = StockOpname::create([
            'user_id' => $creator->id,
            'division_id' => null,
            'opname_date' => now(),
            'status' => 'Draft',
        ]);
        
        expect(fn () => $this->service->delete($opname, $otherUser))
            ->toThrow(\Exception::class, 'Unauthorized');
    });
});

// ============================================
// confirm
// ============================================

describe('confirm', function () {
    
    it('dapat mengkonfirmasi opname Draft', function () {
        $user = User::factory()->create();
        
        $opname = StockOpname::create([
            'user_id' => $user->id,
            'division_id' => null,
            'opname_date' => now(),
            'status' => 'Draft',
        ]);
        $opname->items()->create([
            'item_id' => $this->warehouseItem->id,
            'system_stock' => 100,
            'physical_stock' => 95,
            'difference' => -5,
        ]);
        
        $confirmed = $this->service->confirm($opname, $user);
        
        expect($confirmed->status)->toBe('Confirmed');
    });
    
    it('stok item diupdate sesuai physical_stock', function () {
        $user = User::factory()->create();
        
        $opname = StockOpname::create([
            'user_id' => $user->id,
            'division_id' => null,
            'opname_date' => now(),
            'status' => 'Draft',
        ]);
        $opname->items()->create([
            'item_id' => $this->warehouseItem->id,
            'system_stock' => 100,
            'physical_stock' => 85, // New stock should be 85
            'difference' => -15,
        ]);
        
        $this->service->confirm($opname, $user);
        
        $this->warehouseItem->refresh();
        expect($this->warehouseItem->stock)->toBe(85);
    });
    
    it('membuat transaksi untuk setiap perbedaan stok', function () {
        $user = User::factory()->create();
        
        $opname = StockOpname::create([
            'user_id' => $user->id,
            'division_id' => null,
            'opname_date' => now(),
            'status' => 'Draft',
        ]);
        $opname->items()->create([
            'item_id' => $this->warehouseItem->id,
            'system_stock' => 100,
            'physical_stock' => 90,
            'difference' => -10,
        ]);
        
        $this->service->confirm($opname, $user);
        
        $transaction = ItemTransaction::where('item_id', $this->warehouseItem->id)->first();
        expect($transaction)->not->toBeNull();
        expect($transaction->quantity)->toBe(10);
        expect($transaction->user_id)->toBe($user->id);
    });
    
    it('gagal konfirmasi jika sudah Confirmed', function () {
        $user = User::factory()->create();
        
        $opname = StockOpname::create([
            'user_id' => $user->id,
            'division_id' => null,
            'opname_date' => now(),
            'status' => 'Confirmed', // Already confirmed
        ]);
        
        expect(fn () => $this->service->confirm($opname, $user))
            ->toThrow(\Exception::class, 'sudah dikonfirmasi');
    });
});

// ============================================
// canManage & canView
// ============================================

describe('canManage dan canView', function () {
    
    it('creator dapat manage opname Draft gudang dengan permission kelola_gudang', function () {
        $role = Role::firstOrCreate(['name' => 'Warehouse Manager', 'guard_name' => 'web']);
        $role->syncPermissions([InventoryPermission::ManageWarehouseStockOpname->value]);
        
        $user = User::factory()->create();
        $user->assignRole($role);
        
        $opname = StockOpname::create([
            'user_id' => $user->id,
            'division_id' => null,
            'opname_date' => now(),
            'status' => 'Draft',
        ]);
        
        expect($this->service->canManage($opname, $user))->toBeTrue();
    });
    
    it('tidak dapat manage jika status sudah Confirmed', function () {
        $role = Role::firstOrCreate(['name' => 'Warehouse Manager', 'guard_name' => 'web']);
        $role->syncPermissions([InventoryPermission::ManageWarehouseStockOpname->value]);
        
        $user = User::factory()->create();
        $user->assignRole($role);
        
        $opname = StockOpname::create([
            'user_id' => $user->id,
            'division_id' => null,
            'opname_date' => now(),
            'status' => 'Confirmed',
        ]);
        
        expect($this->service->canManage($opname, $user))->toBeFalse();
    });
    
    it('user dengan lihat_semua dapat view semua opname', function () {
        $role = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $role->syncPermissions([InventoryPermission::ViewAllStockOpname->value]);
        
        $user = User::factory()->create();
        $user->assignRole($role);
        
        $otherUser = User::factory()->create();
        $opname = StockOpname::create([
            'user_id' => $otherUser->id,
            'division_id' => $this->division->id,
            'opname_date' => now(),
            'status' => 'Draft',
        ]);
        
        expect($this->service->canView($opname, $user))->toBeTrue();
    });
});

// ============================================
// getType
// ============================================

describe('getType', function () {
    
    it('mengembalikan warehouse jika division_id null', function () {
        $opname = new StockOpname(['division_id' => null]);
        
        expect($this->service->getType($opname))->toBe('warehouse');
    });
    
    it('mengembalikan division jika division_id ada', function () {
        $opname = new StockOpname(['division_id' => 1]);
        
        expect($this->service->getType($opname))->toBe('division');
    });
});
