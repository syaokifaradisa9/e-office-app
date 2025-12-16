<?php

namespace Tests\Unit\Inventory;

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
    
    it('dapat membuat kategori baru dengan data valid', function () {
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
    
    it('dapat mengupdate kategori yang sudah ada', function () {
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
    
    it('dapat menghapus kategori', function () {
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
    
    it('cache ter-invalidasi setelah store', function () {
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
    
    it('cache ter-invalidasi setelah update', function () {
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
    
    it('cache ter-invalidasi setelah delete', function () {
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
    
    it('mengembalikan semua kategori aktif dengan benar', function () {
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
    
    it('cache kategori aktif setelah dipanggil pertama kali', function () {
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
    
    it('dapat mentransformasi ke model payload dengan benar', function () {
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
    
    it('dapat menangani description null', function () {
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
