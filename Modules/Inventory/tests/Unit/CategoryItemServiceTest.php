<?php

namespace Modules\Inventory\Tests\Unit;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\Inventory\DataTransferObjects\StoreCategoryItemDTO;
use Modules\Inventory\DataTransferObjects\UpdateCategoryItemDTO;
use Modules\Inventory\Models\CategoryItem;
use Modules\Inventory\Repositories\CategoryItem\CategoryItemRepository;
use Modules\Inventory\Services\CategoryItemService;
use Mockery;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Clear cache sebelum setiap test
    Cache::flush();
});

describe('CategoryItemService', function () {
    
    /**
     * Memastikan service dapat membuat kategori baru jika diberikan data DTO yang valid.
     */
    it('can create a new category with valid data', function () {
        // Arrange
        $dto = new StoreCategoryItemDTO(
            name: 'Kategori Test',
            description: 'Deskripsi kategori test',
            isActive: true
        );
        
        $mockCategory = new CategoryItem([
            'id' => 1,
            'name' => 'Kategori Test',
            'description' => 'Deskripsi kategori test',
            'is_active' => true
        ]);
        
        $mockRepo = Mockery::mock(CategoryItemRepository::class);
        $mockRepo->shouldReceive('create')
            ->once()
            ->with([
                'name' => 'Kategori Test',
                'description' => 'Deskripsi kategori test',
                'is_active' => true
            ])
            ->andReturn($mockCategory);
        
        $service = new CategoryItemService($mockRepo);
        
        // Act
        $result = $service->store($dto);
        
        // Assert
        expect($result)->toBeInstanceOf(CategoryItem::class);
        expect($result->name)->toBe('Kategori Test');
    });
    
    /**
     * Memastikan service dapat memperbarui data kategori yang sudah ada melalui repository.
     */
    it('can update an existing category', function () {
        // Arrange
        $existingCategory = new CategoryItem([
            'id' => 1,
            'name' => 'Old Name',
            'description' => 'Old Description',
            'is_active' => true
        ]);
        
        $dto = new UpdateCategoryItemDTO(
            name: 'New Name',
            description: 'New Description',
            isActive: false
        );
        
        $updatedCategory = new CategoryItem([
            'id' => 1,
            'name' => 'New Name',
            'description' => 'New Description',
            'is_active' => false
        ]);
        
        $mockRepo = Mockery::mock(CategoryItemRepository::class);
        $mockRepo->shouldReceive('update')
            ->once()
            ->with($existingCategory, [
                'name' => 'New Name',
                'description' => 'New Description',
                'is_active' => false
            ])
            ->andReturn($updatedCategory);
        
        $service = new CategoryItemService($mockRepo);
        
        // Act
        $result = $service->update($existingCategory, $dto);
        
        // Assert
        expect($result->name)->toBe('New Name');
        expect($result->is_active)->toBeFalse();
    });
    
    /**
     * Memastikan service dapat menghapus data kategori melalui repository.
     */
    it('can delete a category', function () {
        // Arrange
        $category = new CategoryItem([
            'id' => 1,
            'name' => 'To Delete',
            'description' => 'Will be deleted',
            'is_active' => true
        ]);
        
        $mockRepo = Mockery::mock(CategoryItemRepository::class);
        $mockRepo->shouldReceive('delete')
            ->once()
            ->with($category)
            ->andReturn(true);
        
        $service = new CategoryItemService($mockRepo);
        
        // Act
        $result = $service->delete($category);
        
        // Assert
        expect($result)->toBeTrue();
    });
    
    /**
     * Memastikan cache kategori aktif dihapus (invalidasi) setelah proses penyimpanan data baru.
     */
    it('invalidates cache after storing a new category', function () {
        // Arrange
        Cache::put('inventory_categories_active', collect(['cached data']), 3600);
        
        $dto = new StoreCategoryItemDTO(
            name: 'New Category',
            description: null,
            isActive: true
        );
        
        $mockCategory = new CategoryItem(['id' => 1, 'name' => 'New Category']);
        
        $mockRepo = Mockery::mock(CategoryItemRepository::class);
        $mockRepo->shouldReceive('create')->andReturn($mockCategory);
        
        $service = new CategoryItemService($mockRepo);
        
        // Act
        $service->store($dto);
        
        // Assert
        expect(Cache::has('inventory_categories_active'))->toBeFalse();
    });
    
    /**
     * Memastikan cache kategori aktif dihapus setelah proses pembaruan data.
     */
    it('invalidates cache after updating a category', function () {
        // Arrange
        Cache::put('inventory_categories_active', collect(['cached data']), 3600);
        
        $category = new CategoryItem(['id' => 1, 'name' => 'Old']);
        $dto = new UpdateCategoryItemDTO(name: 'New', description: null, isActive: true);
        
        $mockRepo = Mockery::mock(CategoryItemRepository::class);
        $mockRepo->shouldReceive('update')->andReturn($category);
        
        $service = new CategoryItemService($mockRepo);
        
        // Act
        $service->update($category, $dto);
        
        // Assert
        expect(Cache::has('inventory_categories_active'))->toBeFalse();
    });
    
    /**
     * Memastikan cache kategori aktif dihapus setelah proses penghapusan data.
     */
    it('invalidates cache after deleting a category', function () {
        // Arrange
        Cache::put('inventory_categories_active', collect(['cached data']), 3600);
        
        $category = new CategoryItem(['id' => 1, 'name' => 'To Delete']);
        
        $mockRepo = Mockery::mock(CategoryItemRepository::class);
        $mockRepo->shouldReceive('delete')->andReturn(true);
        
        $service = new CategoryItemService($mockRepo);
        
        // Act
        $service->delete($category);
        
        // Assert
        expect(Cache::has('inventory_categories_active'))->toBeFalse();
    });
    
    /**
     * Memastikan service mengembalikan koleksi kategori yang berstatus aktif dengan benar.
     */
    it('returns all active categories correctly', function () {
        // Arrange
        $categories = new EloquentCollection([
            new CategoryItem(['id' => 1, 'name' => 'Cat A', 'is_active' => true]),
            new CategoryItem(['id' => 2, 'name' => 'Cat B', 'is_active' => true]),
        ]);
        
        $mockRepo = Mockery::mock(CategoryItemRepository::class);
        $mockRepo->shouldReceive('getActiveCategories')
            ->once()
            ->andReturn($categories);
        
        $service = new CategoryItemService($mockRepo);
        
        // Act
        $result = $service->getActiveCategories();
        
        // Assert
        expect($result)->toHaveCount(2);
        expect($result->first()->name)->toBe('Cat A');
    });
    
    /**
     * Memastikan data kategori aktif disimpan di cache guna mengoptimalkan performa pada pemanggilan berikutnya.
     */
    it('caches active categories after the first call', function () {
        // Arrange
        $categories = new EloquentCollection([
            new CategoryItem(['id' => 1, 'name' => 'Cached', 'is_active' => true]),
        ]);
        
        $mockRepo = Mockery::mock(CategoryItemRepository::class);
        $mockRepo->shouldReceive('getActiveCategories')
            ->once() // Hanya sekali karena kedua panggilan harusnya dari cache
            ->andReturn($categories);
        
        $service = new CategoryItemService($mockRepo);
        
        // Act
        $service->getActiveCategories();
        $result = $service->getActiveCategories();
        
        // Assert
        expect($result)->toHaveCount(1);
        expect(Cache::has('inventory_categories_active'))->toBeTrue();
    });
});

describe('StoreCategoryItemDTO', function () {
    
    /**
     * Memastikan DTO dapat mengonversi properti class menjadi array payload untuk model Eloquent.
     */
    it('transforms to model payload correctly', function () {
        // Arrange
        $dto = new StoreCategoryItemDTO(
            name: 'Test Category',
            description: 'Test Description',
            isActive: true
        );
        
        // Act
        $payload = $dto->toModelPayload();
        
        // Assert
        expect($payload)->toBe([
            'name' => 'Test Category',
            'description' => 'Test Description',
            'is_active' => true
        ]);
    });
    
    /**
     * Memastikan DTO menangani input deskripsi yang bernilai null dengan benar.
     */
    it('handles null description correctly', function () {
        // Arrange
        $dto = new StoreCategoryItemDTO(
            name: 'No Description',
            description: null,
            isActive: false
        );
        
        // Act
        $payload = $dto->toModelPayload();
        
        // Assert
        expect($payload['description'])->toBeNull();
        expect($payload['is_active'])->toBeFalse();
    });
});
