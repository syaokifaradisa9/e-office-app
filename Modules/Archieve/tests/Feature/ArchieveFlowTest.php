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
        ArchieveUserPermission::ViewDivision->value,
        ArchieveUserPermission::ManageDivision->value,
        ArchieveUserPermission::SearchDivisionScope->value,
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

describe('Page Access & Permission', function () {
    
    /**
     * Memastikan user tanpa izin ditolak akses ke indeks dokumen (403).
     */
    it('denies index access without permission', function () {
        // 1. Persiapan user tanpa role/izin
        $user = User::factory()->create();
        
        // 2. Akses ditolak
        $response = $this->actingAs($user)->get('/archieve/documents');
        $response->assertForbidden();
    });

    /**
     * Memastikan Admin dengan izin lengkap dapat mengakses semua halaman arsip.
     */
    it('allows admin to access all pages', function () {
        // 1. Acting sebagai admin
        $this->actingAs($this->adminUser);
        
        // 2. Cek akses ke berbagai modul utama
        $this->get('/archieve/documents')->assertOk();
        $this->get('/archieve/categories')->assertOk();
        $this->get('/archieve/classifications')->assertOk();
        $this->get('/archieve/division-storages')->assertOk();
        $this->get('/archieve/reports/all')->assertOk();
    });

    /**
     * Memastikan staf divisi memiliki akses terbatas (hanya ke dokumen dan laporan divisi).
     */
    it('restricts division staff access', function () {
        // 1. Acting sebagai user divisi
        $this->actingAs($this->divisionUser);
        
        // 2. Akses halaman yang diizinkan
        $this->get('/archieve/documents')->assertOk();
        $this->get('/archieve/reports')->assertOk();
        
        // 3. Akses halaman admin/global harus ditolak (403)
        $this->get('/archieve/categories')->assertForbidden();
        $this->get('/archieve/classifications')->assertForbidden();
        $this->get('/archieve/division-storages')->assertForbidden();
        $this->get('/archieve/reports/all')->assertForbidden();
    });
});

// ============================================
// DOCUMENT FLOW TESTS
// ============================================

describe('Document Management Flow', function () {

    /**
     * Test alur pengunggahan dokumen baru oleh user divisi.
     * Memastikan file tersimpan dan kuota divisi terpotong.
     */
    it('can upload new document and allocate storage', function () {
        // 1. Persiapan file dummy (1MB)
        $file = UploadedFile::fake()->create('test_document.pdf', 1024); // 1MB
        
        // 2. Aksi simpan dokumen baru
        $response = $this->actingAs($this->divisionUser)->post('/archieve/documents', [
            'title' => 'Dokumen Kerja 2024',
            'classification_id' => $this->classification->id,
            'category_ids' => [$this->category->id],
            'division_ids' => [$this->division->id],
            'file' => $file,
        ]);

        // 3. Validasi redirect sukses
        $response->assertRedirect();
        
        // 4. Pastikan record ada di DB
        $this->assertDatabaseHas('archieve_documents', [
            'title' => 'Dokumen Kerja 2024',
            'uploaded_by' => $this->divisionUser->id
        ]);

        // 5. Validasi kuota storage divisi terpakai 1MB
        $storage = DivisionStorage::where('division_id', $this->division->id)->first();
        expect($storage->used_size)->toBe(1024 * 1024);
        
        // 6. Validasi file fisik ada di storage disk
        $document = Document::latest()->first();
        Storage::disk('public')->assertExists($document->file_path);
    });

    /**
     * Test alur pembaruan dokumen (re-upload).
     * Memastikan kuota lama dilepas dan kuota baru dialokasikan.
     */
    it('can update document and relocate storage', function () {
        // 1. Persiapan dokumen awal (500KB)
        $oldSize = 500 * 1024; // 500KB
        $document = Document::factory()->create([
            'file_size' => $oldSize,
            'uploaded_by' => $this->divisionUser->id,
            'classification_id' => $this->classification->id
        ]);
        $document->divisions()->attach($this->division->id, ['allocated_size' => $oldSize]);
        
        $storage = DivisionStorage::where('division_id', $this->division->id)->first();
        $storage->update(['used_size' => $oldSize]);

        // 2. Aksi update dengan file baru (1MB)
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

        // 3. Validasi redirect dan update kuota storage (dari 500KB jadi 1MB)
        $response->assertRedirect();
        
        $storage->refresh();
        expect($storage->used_size)->toBe($newSizeKB * 1024);
    });

    /**
     * Memastikan kuota storage dikembalikan (menjadi 0) saat dokumen dihapus.
     */
    it('restores storage quota when document is deleted', function () {
        // 1. Persiapan dokumen (2MB) dan pemakaian storage
        $size = 2000 * 1024; // 2MB
        $document = Document::factory()->create([
            'file_size' => $size,
            'classification_id' => $this->classification->id
        ]);
        $document->divisions()->attach($this->division->id, ['allocated_size' => $size]);
        
        $storage = DivisionStorage::where('division_id', $this->division->id)->first();
        $storage->update(['used_size' => $size]);

        // 2. Aksi hapus dokumen
        $response = $this->actingAs($this->adminUser)->delete("/archieve/documents/{$document->id}");
        
        // 3. Validasi redirect dan kuota storage kembali ke 0
        $response->assertRedirect();
        
        $storage->refresh();
        expect($storage->used_size)->toBe(0);
        $this->assertDatabaseMissing('archieve_documents', ['id' => $document->id]);
    });
});

// ============================================
// SEARCH & SCOPE TESTS
// ============================================

describe('Search Integration & Scope', function () {

    /**
     * Memastikan penegakan scope pencarian (personal/divisi) bekerja dengan benar.
     */
    it('only shows own division documents when using division scope', function () {
        // 1. Persiapan data di 2 divisi berbeda
        $otherDivision = Division::factory()->create();
        
        $myDoc = Document::factory()->create(['title' => 'My Div Doc', 'classification_id' => $this->classification->id]);
        $myDoc->divisions()->attach($this->division->id, ['allocated_size' => 0]);
        
        $otherDoc = Document::factory()->create(['title' => 'Other Div Doc', 'classification_id' => $this->classification->id]);
        $otherDoc->divisions()->attach($otherDivision->id, ['allocated_size' => 0]);

        // 2. Aksi pencarian sebagai user divisi
        $response = $this->actingAs($this->divisionUser)->get('/archieve/documents/search/results');
        
        // 3. Validasi: Hanya dokumen dari divisi sendiri yang muncul
        $data = $response->json();
        $titles = collect($data['data'])->pluck('title');
        
        expect($titles)->toContain('My Div Doc');
        expect($titles)->not->toContain('Other Div Doc');
    });

    /**
     * Memastikan Admin dapat mencari semua dokumen tanpa batasan scope divisi.
     */
    it('allows admin to search all documents without division scope restriction', function () {
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

describe('Dashboard & Reports', function () {

    /**
     * Test integrasi dashboard: Memastikan tab yang muncul sesuai role user.
     */
    it('returns dashboard data matching user role', function () {
        // 1. Akses dashboard
        $response = $this->actingAs($this->divisionUser)->get('/archieve/dashboard');
        
        // 2. Validasi status dan tab (harusnya hanya tab 'division')
        $response->assertOk();
        $prop = $response->inertiaPage()['props'];
        
        expect($prop['tabs'])->toHaveCount(1);
        expect($prop['tabs'][0]['id'])->toBe('division');
    });

    /**
     * Test integrasi laporan: Memastikan statistik (jumlah dokumen & size) akurat.
     */
    it('returns accurate division report data', function () {
        // 1. Persiapan 1 dokumen (5MB)
        $size = 5 * 1024 * 1024; // 5MB
        $doc = Document::factory()->create([
            'file_size' => $size,
            'classification_id' => $this->classification->id
        ]);
        $doc->divisions()->attach($this->division->id, ['allocated_size' => $size]);

        // 2. Akses halaman laporan
        $response = $this->actingAs($this->divisionUser)->get('/archieve/reports');
        
        // 3. Validasi statistik data yang dikirim ke Inertia
        $response->assertOk();
        $data = $response->inertiaPage()['props']['reportData'];
        
        expect($data['overview_stats']['storage_used'])->toBe($size);
        expect($data['overview_stats']['total_documents'])->toBe(1);
    });
});
