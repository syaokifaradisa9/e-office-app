<?php

namespace Modules\Inventory\Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Inventory\Enums\ItemTransactionType;
use Modules\Inventory\Models\CategoryItem;
use Modules\Inventory\Models\Item;
use Modules\Inventory\Models\ItemTransaction;
use Modules\Inventory\Services\ItemService;

uses(RefreshDatabase::class);

describe('ItemService', function () {
    
    beforeEach(function () {
        $this->service = app(ItemService::class);
        $this->user = User::factory()->create();
        $this->category = CategoryItem::factory()->create();
    });
    
    /**
     * Memastikan service dapat mengurangi stok dengan jumlah pengeluaran yang valid.
     */
    it('can issue stock with an authorized and valid quantity', function () {
        // Arrange
        $item = Item::factory()->create([
            'category_id' => $this->category->id,
            'stock' => 100,
        ]);
        
        // Act
        $this->service->issueStock($item, 10, 'Test pengeluaran stok', $this->user);
        
        // Assert
        $item->refresh();
        expect($item->stock)->toBe(90);
    });
    
    /**
     * Memastikan jumlah stok barang di database benar-benar berkurang setelah proses issue berhasil.
     */
    it('reduces stock after a successful issue process', function () {
        // Arrange
        $item = Item::factory()->create([
            'category_id' => $this->category->id,
            'stock' => 50,
        ]);
        $initialStock = $item->stock;
        $quantityToIssue = 15;
        
        // Act
        $this->service->issueStock($item, $quantityToIssue, 'Pengeluaran untuk divisi', $this->user);
        
        // Assert
        $item->refresh();
        expect($item->stock)->toBe($initialStock - $quantityToIssue);
    });
    
    /**
     * Memastikan catatan transaksi (item_transaction) dibuat secara otomatis setelah proses issue stok.
     */
    it('creates an item_transaction record after issuing stock', function () {
        // Arrange
        $item = Item::factory()->create([
            'category_id' => $this->category->id,
            'stock' => 100,
        ]);
        
        // Act
        $this->service->issueStock($item, 25, 'Test transaksi', $this->user);
        
        // Assert
        $transaction = ItemTransaction::where('item_id', $item->id)->first();
        expect($transaction)->not->toBeNull();
        expect($transaction->quantity)->toBe(25);
        expect($transaction->type->value)->toBe(ItemTransactionType::Out->value);
        expect($transaction->user_id)->toBe($this->user->id);
        expect($transaction->description)->toBe('Test transaksi');
    });
    
    /**
     * Memastikan service dapat menangani pengeluaran seluruh stok yang tersedia hingga mencapai nol.
     */
    it('can issue the entire available stock correctly', function () {
        // Arrange
        $item = Item::factory()->create([
            'category_id' => $this->category->id,
            'stock' => 30,
        ]);
        
        // Act
        $this->service->issueStock($item, 30, 'Keluarkan semua', $this->user);
        
        // Assert
        $item->refresh();
        expect($item->stock)->toBe(0);
    });
    
    /**
     * Memastikan service dapat menangani pengeluaran stok yang dilakukan beberapa kali secara berurutan.
     */
    it('can issue stock multiple times sequentially', function () {
        // Arrange
        $item = Item::factory()->create([
            'category_id' => $this->category->id,
            'stock' => 100,
        ]);
        
        // Act - Issue 3 times
        $this->service->issueStock($item, 20, 'Issue 1', $this->user);
        $item->refresh();
        expect($item->stock)->toBe(80);
        
        $this->service->issueStock($item, 30, 'Issue 2', $this->user);
        $item->refresh();
        expect($item->stock)->toBe(50);
        
        $this->service->issueStock($item, 50, 'Issue 3', $this->user);
        $item->refresh();
        expect($item->stock)->toBe(0);
        
        // Assert - 3 transactions created
        $transactions = ItemTransaction::where('item_id', $item->id)->count();
        expect($transactions)->toBe(3);
    });

    /**
     * Memastikan service melempar Exception jika user mencoba mengeluarkan stok melebihi jumlah yang ada.
     */
    it('cannot issue more stock than what is currently available', function () {
        // Arrange
        $item = Item::factory()->create([
            'category_id' => $this->category->id,
            'stock' => 50,
        ]);
        
        // Act & Assert
        expect(fn () => $this->service->issueStock($item, 100, 'Over stock', $this->user))
            ->toThrow(\Exception::class, 'Stok tidak mencukupi');

        $item->refresh();
        expect($item->stock)->toBe(50);
    });

    /**
     * Memastikan service melempar Exception jika user mencoba mengeluarkan stok dengan jumlah nol atau negatif.
     */
    it('cannot issue stock with zero or negative quantity', function () {
        // Arrange
        $item = Item::factory()->create([
            'category_id' => $this->category->id,
            'stock' => 50,
        ]);
        
        // Act & Assert - Zero
        expect(fn () => $this->service->issueStock($item, 0, 'Zero quantity', $this->user))
            ->toThrow(\Exception::class, 'Jumlah pengeluaran harus lebih besar dari nol');

        // Act & Assert - Negative
        expect(fn () => $this->service->issueStock($item, -10, 'Negative quantity', $this->user))
            ->toThrow(\Exception::class, 'Jumlah pengeluaran harus lebih besar dari nol');

        $item->refresh();
        expect($item->stock)->toBe(50);
    });
});
