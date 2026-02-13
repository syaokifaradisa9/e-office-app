<?php

use App\Models\User;
use App\Models\Division;
use Modules\Archieve\Models\Document;
use Modules\Archieve\Models\DivisionStorage;
use Modules\Archieve\Enums\ArchieveUserPermission;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Inertia\Testing\AssertableInertia as Assert;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    // Setup Permissions
    foreach (ArchieveUserPermission::values() as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }

    $this->division = Division::factory()->create(['name' => 'IT Department']);
    $this->otherDivision = Division::factory()->create(['name' => 'HR Department']);

    // Create roles for specific testing
    $this->divisionRole = Role::firstOrCreate(['name' => 'Division Dashboard Viewer', 'guard_name' => 'web']);
    $this->divisionRole->givePermissionTo(ArchieveUserPermission::ViewDashboardDivision->value);

    $this->globalRole = Role::firstOrCreate(['name' => 'Global Dashboard Viewer', 'guard_name' => 'web']);
    $this->globalRole->givePermissionTo(ArchieveUserPermission::ViewDashboardAll->value);
});

describe('Dashboard Access Control', function () {

    /**
     * Memastikan user tanpa izin dashboard arsip ditolak (403).
     */
    it('denies access to users without any dashboard permissions', function () {
        // 1. Create user polosan
        $user = User::factory()->create();
        
        // 2. Akses ditolak
        $this->actingAs($user)
            ->get('/archieve/dashboard')
            ->assertStatus(403);
    });

    /**
     * Memastikan user dengan izin Dashboard Division hanya melihat tab divisi.
     */
    it('only shows division tab for users with division dashboard permission', function () {
        // 1. Persiapan user dengan role divisi
        $user = User::factory()->create(['division_id' => $this->division->id]);
        $user->assignRole($this->divisionRole);

        // 2. Akses dashboard
        $response = $this->actingAs($user)->get('/archieve/dashboard');
        
        // 3. Validasi tab yang muncul hanya 1 (divisi)
        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Archieve/Dashboard/Index')
            ->has('tabs', 1)
            ->where('tabs.0.id', 'division')
            ->where('tabs.0.type', 'division')
        );
    });

    /**
     * Memastikan user dengan izin Dashboard All hanya melihat tab overview global.
     */
    it('only shows overall tab for users with global dashboard permission', function () {
        // 1. Persiapan user dengan role global
        $user = User::factory()->create();
        $user->assignRole($this->globalRole);

        // 2. Akses dashboard
        $response = $this->actingAs($user)->get('/archieve/dashboard');
        
        // 3. Validasi tab yang muncul hanya 1 (overview)
        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Archieve/Dashboard/Index')
            ->has('tabs', 1)
            ->where('tabs.0.id', 'all')
            ->where('tabs.0.type', 'overview')
        );
    });

    /**
     * Memastikan user dengan kedua izin (divisi & global) melihat kedua tab tersebut.
     */
    it('shows both tabs for users with both permissions', function () {
        // 1. Persiapan user dengan kedua izin
        $user = User::factory()->create(['division_id' => $this->division->id]);
        $user->givePermissionTo(ArchieveUserPermission::ViewDashboardDivision->value);
        $user->givePermissionTo(ArchieveUserPermission::ViewDashboardAll->value);

        // 2. Akses dashboard
        $response = $this->actingAs($user)->get('/archieve/dashboard');
        
        // 3. Validasi kedua tab muncul
        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Archieve/Dashboard/Index')
            ->has('tabs', 2)
            ->where('tabs.0.id', 'division')
            ->where('tabs.1.id', 'all')
        );
    });
});

describe('Dashboard Data Accuracy', function () {

    /**
     * Memastikan akurasi perhitungan kapasitas penyimpanan dan jumlah dokumen per divisi di dashboard.
     */
    it('calculates division storage usage and document count correctly', function () {
        // 1. Persiapan user dan limit penyimpanan divisi (100MB)
        $user = User::factory()->create(['division_id' => $this->division->id]);
        $user->assignRole($this->divisionRole);

        $maxSize = 100 * 1024 * 1024; // 100MB
        DivisionStorage::factory()->create([
            'division_id' => $this->division->id,
            'max_size' => $maxSize,
        ]);

        // 2. Tambah 3 dokumen (@5MB total 15MB)
        $docSize = 5 * 1024 * 1024; // 5MB each
        $docs = Document::factory()->count(3)->create([
            'file_size' => $docSize,
        ]);
        
        foreach ($docs as $doc) {
            $doc->divisions()->attach($this->division->id, ['allocated_size' => $docSize]);
        }
        
        // 3. Akses dashboard dan cek data statistik divisi
        $response = $this->actingAs($user)->get('/archieve/dashboard');
        
        $response->assertInertia(fn (Assert $page) => $page
            ->where('tabs.0.data.document_count', 3)
            ->where('tabs.0.data.storage.used', 15 * 1024 * 1024)
            ->where('tabs.0.data.storage.max', $maxSize)
            ->where('tabs.0.data.storage.percentage', 15)
        );
    });

    /**
     * Memastikan agregasi data keseluruhan (sistem) di tab overview benar.
     */
    it('aggregates system-wide data correctly in the overall tab', function () {
        // 1. Persiapan user global
        $user = User::factory()->create();
        $user->assignRole($this->globalRole);

        // 2. Tambah dokumen di 2 divisi berbeda (total 30MB)
        $doc1 = Document::factory()->create(['file_size' => 10 * 1024 * 1024]);
        $doc1->divisions()->attach($this->division->id, ['allocated_size' => 10 * 1024 * 1024]);

        $doc2 = Document::factory()->create(['file_size' => 20 * 1024 * 1024]);
        $doc2->divisions()->attach($this->otherDivision->id, ['allocated_size' => 20 * 1024 * 1024]);

        // 3. Validasi total dokumen dan total ukuran di dashboard global
        $response = $this->actingAs($user)->get('/archieve/dashboard');
        
        $response->assertInertia(fn (Assert $page) => $page
            ->where('tabs.0.data.total_documents', 2)
            ->where('tabs.0.data.total_size', 30 * 1024 * 1024)
        );
    });
});

describe('Dashboard Edge Cases', function () {

    /**
     * Memastikan tab divisi tidak muncul jika user punya izin tapi tidak ditugaskan ke divisi manapun.
     */
    it('does not show division tab if user has permission but no division assigned', function () {
        // 1. User dengan izin divisi tapi division_id null
        $user = User::factory()->create(['division_id' => null]);
        $user->assignRole($this->divisionRole);

        // 2. Akses dashboard
        $response = $this->actingAs($user)->get('/archieve/dashboard');
        
        // 3. Validasi tab kosong (0)
        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->has('tabs', 0)
        );
    });

    /**
     * Menangani kondisi di mana divisi belum memiliki record pengaturan storage (limit 0).
     */
    it('handles division with zero or null storage correctly', function () {
        // 1. User tanpa record di tabel division_storages
        $user = User::factory()->create(['division_id' => $this->division->id]);
        $user->assignRole($this->divisionRole);

        // 2. Akses dashboard
        $response = $this->actingAs($user)->get('/archieve/dashboard');
        
        // 3. Validasi limit storage adalah 0
        $response->assertInertia(fn (Assert $page) => $page
            ->where('tabs.0.data.storage.max', 0)
            ->where('tabs.0.data.storage.percentage', 0)
        );
    });
});
