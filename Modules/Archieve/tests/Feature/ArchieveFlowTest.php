<?php

use App\Models\User;
use App\Models\Division;
use Modules\Archieve\Models\Document;
use Modules\Archieve\Models\Category;
use Modules\Archieve\Models\CategoryContext;
use Modules\Archieve\Models\DocumentClassification;
use Modules\Archieve\Models\DivisionStorage;
use Modules\Archieve\Enums\ArchieveUserPermission;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    // Setup Permissions
    foreach (ArchieveUserPermission::values() as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }

    // Role with Full Access
    $this->adminRole = Role::firstOrCreate(['name' => 'Archieve Admin', 'guard_name' => 'web']);
    $this->adminRole->syncPermissions(ArchieveUserPermission::values());

    // Role with Division Access
    $this->divisionRole = Role::firstOrCreate(['name' => 'Division Staff', 'guard_name' => 'web']);
    $this->divisionRole->syncPermissions([
        ArchieveUserPermission::ViewDocument->value,
        ArchieveUserPermission::ViewDivision->value,
        ArchieveUserPermission::ManageDivision->value,
        ArchieveUserPermission::SearchDivisionScope->value,
        ArchieveUserPermission::SearchDocument->value, // Added
        ArchieveUserPermission::ViewDashboardDivision->value,
        ArchieveUserPermission::ViewReportDivision->value,
    ]);

    $this->division = Division::factory()->create(['name' => 'IT Division']);
    
    $this->adminUser = User::factory()->create();
    $this->adminUser->assignRole($this->adminRole);

    $this->divisionUser = User::factory()->create(['division_id' => $this->division->id]);
    $this->divisionUser->assignRole($this->divisionRole);

    // Initial Data
    $this->context = CategoryContext::factory()->create(['name' => 'Legal']);
    $this->category = Category::factory()->create(['context_id' => $this->context->id, 'name' => 'Contract']);
    $this->classification = DocumentClassification::factory()->create(['name' => 'Internal Document', 'code' => 'INT-01']);
    
    DivisionStorage::factory()->create([
        'division_id' => $this->division->id,
        'max_size' => 100 * 1024 * 1024, // 100MB
        'used_size' => 0
    ]);

    Storage::fake('public');
});

// ============================================
// ACCESS & PERMISSION TESTS
// ============================================

describe('Akses Halaman & Permission', function () {
    
    it('menolak akses index jika tidak memiliki permission', function () {
        $user = User::factory()->create(); // No role
        $response = $this->actingAs($user)->get('/archieve/documents');
        $response->assertForbidden();
    });

    it('mengizinkan admin mengakses semua halaman', function () {
        $this->actingAs($this->adminUser);
        
        $this->get('/archieve/documents')->assertOk();
        $this->get('/archieve/categories')->assertOk();
        $this->get('/archieve/classifications')->assertOk();
        $this->get('/archieve/division-storages')->assertOk();
        $this->get('/archieve/reports/all')->assertOk();
    });

    it('membatasi akses staff divisi', function () {
        $this->actingAs($this->divisionUser);
        
        $this->get('/archieve/documents')->assertOk();
        $this->get('/archieve/reports')->assertOk();
        
        // Should be forbidden for global/admin pages
        $this->get('/archieve/categories')->assertForbidden();
        $this->get('/archieve/classifications')->assertForbidden();
        $this->get('/archieve/division-storages')->assertForbidden();
        $this->get('/archieve/reports/all')->assertForbidden();
    });
});

// ============================================
// DOCUMENT FLOW TESTS
// ============================================

describe('Alur Pengelolaan Dokumen', function () {

    it('dapat mengunggah dokumen baru dan mengalokasikan storage', function () {
        $file = UploadedFile::fake()->create('test_document.pdf', 1024); // 1MB
        
        $response = $this->actingAs($this->divisionUser)->post('/archieve/documents', [
            'title' => 'Dokumen Kerja 2024',
            'classification_id' => $this->classification->id,
            'category_ids' => [$this->category->id],
            'division_ids' => [$this->division->id],
            'file' => $file,
        ]);

        $response->assertRedirect();
        
        // Check DB
        $this->assertDatabaseHas('archieve_documents', [
            'title' => 'Dokumen Kerja 2024',
            'uploaded_by' => $this->divisionUser->id
        ]);

        // Check Storage Allocation
        $storage = DivisionStorage::where('division_id', $this->division->id)->first();
        expect($storage->used_size)->toBe(1024 * 1024);
        
        // Check File exists
        $document = Document::latest()->first();
        Storage::disk('public')->assertExists($document->file_path);
    });

    it('dapat memperbarui dokumen dan merelokasi storage', function () {
        $oldSize = 500 * 1024; // 500KB
        $document = Document::factory()->create([
            'file_size' => $oldSize,
            'uploaded_by' => $this->divisionUser->id,
            'classification_id' => $this->classification->id
        ]);
        $document->divisions()->attach($this->division->id, ['allocated_size' => $oldSize]);
        
        // Initial used size
        $storage = DivisionStorage::where('division_id', $this->division->id)->first();
        $storage->update(['used_size' => $oldSize]);

        // Update with new file
        $newSizeKB = 1000; // 1000KB
        $newFile = UploadedFile::fake()->create('new_version.pdf', $newSizeKB);
        
        $response = $this->actingAs($this->divisionUser)->post("/archieve/documents/{$document->id}", [
            'title' => 'Updated Title',
            'classification_id' => $this->classification->id,
            'category_ids' => [$this->category->id],
            'division_ids' => [$this->division->id],
            'file' => $newFile,
            '_method' => 'PUT'
        ]);

        $response->assertRedirect();
        
        $storage->refresh();
        // Old size released, new size allocated
        expect($storage->used_size)->toBe($newSizeKB * 1024);
    });

    it('mengembalikan kuota storage saat dokumen dihapus', function () {
        $size = 2000 * 1024; // 2MB
        $document = Document::factory()->create([
            'file_size' => $size,
            'classification_id' => $this->classification->id
        ]);
        $document->divisions()->attach($this->division->id, ['allocated_size' => $size]);
        
        $storage = DivisionStorage::where('division_id', $this->division->id)->first();
        $storage->update(['used_size' => $size]);

        $response = $this->actingAs($this->adminUser)->delete("/archieve/documents/{$document->id}");
        
        $response->assertRedirect();
        
        $storage->refresh();
        expect($storage->used_size)->toBe(0);
        $this->assertDatabaseMissing('archieve_documents', ['id' => $document->id]);
    });
});

// ============================================
// SEARCH & SCOPE TESTS
// ============================================

describe('Integrasi Pencarian & Scope', function () {

    it('hanya melihat dokumen divisi sendiri jika memiliki scope divisi', function () {
        $otherDivision = Division::factory()->create();
        
        // Doc in own division
        $myDoc = Document::factory()->create(['title' => 'My Div Doc', 'classification_id' => $this->classification->id]);
        $myDoc->divisions()->attach($this->division->id, ['allocated_size' => 0]);
        
        // Doc in other division
        $otherDoc = Document::factory()->create(['title' => 'Other Div Doc', 'classification_id' => $this->classification->id]);
        $otherDoc->divisions()->attach($otherDivision->id, ['allocated_size' => 0]);

        $response = $this->actingAs($this->divisionUser)->get('/archieve/documents/search/results');
        
        $data = $response->json();
        $titles = collect($data['data'])->pluck('title');
        
        expect($titles)->toContain('My Div Doc');
        expect($titles)->not->toContain('Other Div Doc');
    });

    it('admin dapat melihat seluruh dokumen tanpa terbatas scope', function () {
        $otherDivision = Division::factory()->create();
        
        Document::factory()->create(['title' => 'Doc A', 'classification_id' => $this->classification->id])
            ->divisions()->attach($this->division->id, ['allocated_size' => 0]);
            
        Document::factory()->create(['title' => 'Doc B', 'classification_id' => $this->classification->id])
            ->divisions()->attach($otherDivision->id, ['allocated_size' => 0]);

        $response = $this->actingAs($this->adminUser)->get('/archieve/documents/search/results');
        
        $data = $response->json();
        expect(count($data['data']))->toBe(2);
    });
});

// ============================================
// DASHBOARD & REPORT TESTS
// ============================================

describe('Dashboard & Laporan', function () {

    it('mengembalikan data dashboard yang sesuai role', function () {
        $response = $this->actingAs($this->divisionUser)->get('/archieve/dashboard');
        
        $response->assertOk();
        $prop = $response->inertiaPage()['props'];
        
        // Division user should only see division tab
        expect($prop['tabs'])->toHaveCount(1);
        expect($prop['tabs'][0]['id'])->toBe('division');
    });

    it('mengembalikan data laporan divisi yang akurat', function () {
        $size = 5 * 1024 * 1024; // 5MB
        $doc = Document::factory()->create([
            'file_size' => $size,
            'classification_id' => $this->classification->id
        ]);
        $doc->divisions()->attach($this->division->id, ['allocated_size' => $size]);

        $response = $this->actingAs($this->divisionUser)->get('/archieve/reports');
        
        $response->assertOk();
        $data = $response->inertiaPage()['props']['reportData'];
        
        expect($data['overview_stats']['storage_used'])->toBe($size);
        expect($data['overview_stats']['total_documents'])->toBe(1);
    });
});
