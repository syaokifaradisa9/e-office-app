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

    $this->division = Division::factory()->create(['name' => 'Finance Dept']);
    $this->otherDivision = Division::factory()->create(['name' => 'IT Dept']);

    // Roles
    $this->divisionReportRole = Role::firstOrCreate(['name' => 'Division Report Viewer', 'guard_name' => 'web']);
    $this->divisionReportRole->givePermissionTo(ArchieveUserPermission::ViewReportDivision->value);

    $this->globalReportRole = Role::firstOrCreate(['name' => 'Global Report Viewer', 'guard_name' => 'web']);
    $this->globalReportRole->givePermissionTo(ArchieveUserPermission::ViewReportAll->value);
});

describe('Report Access Control', function () {

    /**
     * Memastikan user tanpa izin ditolak saat mengakses laporan divisi.
     */
    it('denies access to division report for users without permission', function () {
        // 1. Create user divisi tanpa izin laporan
        $user = User::factory()->create(['division_id' => $this->division->id]);
        
        // 2. Akses ditolak
        $this->actingAs($user)
            ->get('/archieve/reports')
            ->assertStatus(403);
    });

    /**
     * Memastikan akses laporan global ditolak bagi user tanpa izin.
     */
    it('denies access to global report for users without permission', function () {
        $user = User::factory()->create();
        
        $this->actingAs($user)
            ->get('/archieve/reports/all')
            ->assertStatus(403);
    });

    /**
     * Memastikan user tanpa divisi ditolak (walau punya izin) karena laporan butuh konteks divisi.
     */
    it('denies division report if user has permission but no division', function () {
        // 1. User punya izin laporan tapi kolom division_id null
        $user = User::factory()->create(['division_id' => null]);
        $user->assignRole($this->divisionReportRole);

        // 2. Akses ditolak
        $this->actingAs($user)
            ->get('/archieve/reports')
            ->assertStatus(403);
    });

    /**
     * Memastikan staf yang berwenang dapat melihat laporan divisinya.
     */
    it('allows access to division report for authorized staff', function () {
        // 1. User dengan role laporan divisi
        $user = User::factory()->create(['division_id' => $this->division->id]);
        $user->assignRole($this->divisionReportRole);

        // 2. Akses halaman laporan divisi
        $this->actingAs($user)
            ->get('/archieve/reports')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Archieve/Report/Index')
                ->has('reportData')
            );
    });

    /**
     * Memastikan Admin dengan izin yang sesuai dapat mengakses laporan global.
     */
    it('allows access to global report for authorized admins', function () {
        $user = User::factory()->create();
        $user->assignRole($this->globalReportRole);

        $this->actingAs($user)
            ->get('/archieve/reports/all')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Archieve/Report/All')
                ->has('reportData')
            );
    });
});

describe('Report Data Accuracy', function () {

    /**
     * Memastikan akurasi statistik laporan (jumlah dokumen, total size, % storage) per divisi.
     */
    it('provides accurate statistics for division reports', function () {
        // 1. Persiapan user dan storage divisi (500MB)
        $user = User::factory()->create(['division_id' => $this->division->id]);
        $user->assignRole($this->divisionReportRole);

        DivisionStorage::factory()->create([
            'division_id' => $this->division->id,
            'max_size' => 500 * 1024 * 1024, // 500MB
        ]);

        // 2. Tambah 5 dokumen (@1MB) ke divisi ini (total 5MB)
        $docs = Document::factory()->count(5)->create(['file_size' => 1024 * 1024]); // 1MB each
        foreach ($docs as $doc) {
            $doc->divisions()->attach($this->division->id, ['allocated_size' => 1024 * 1024]);
        }

        // 3. Tambah dokumen di divisi LAIN (tidak boleh ikut terhitung)
        $otherDoc = Document::factory()->create(['file_size' => 10 * 1024 * 1024]);
        $otherDoc->divisions()->attach($this->otherDivision->id, ['allocated_size' => 10 * 1024 * 1024]);

        // 4. Akses laporan divisi
        $response = $this->actingAs($user)->get('/archieve/reports');

        // 5. Validasi statistik data
        $response->assertInertia(fn (Assert $page) => $page
            ->where('reportData.division_name', 'Finance Dept')
            ->where('reportData.overview_stats.total_documents', 5)
            ->where('reportData.overview_stats.total_size', 5 * 1024 * 1024)
            ->where('reportData.overview_stats.storage_percentage', 1) // 5MB / 500MB = 1%
        );
    });

    /**
     * Memastikan data agregasi global (total dokumen dan total ukuran) dihitung dengan benar.
     */
    it('provides accurate aggregated data for global reports', function () {
        $user = User::factory()->create();
        $user->assignRole($this->globalReportRole);

        // 1. Dokumen di Divisi A (2MB)
        $docA = Document::factory()->create(['file_size' => 2 * 1024 * 1024]);
        $docA->divisions()->attach($this->division->id, ['allocated_size' => 2 * 1024 * 1024]);

        // 2. Dokumen di Divisi B (3MB)
        $docB = Document::factory()->create(['file_size' => 3 * 1024 * 1024]);
        $docB->divisions()->attach($this->otherDivision->id, ['allocated_size' => 3 * 1024 * 1024]);

        // 3. Akses laporan global
        $response = $this->actingAs($user)->get('/archieve/reports/all');

        // 4. Validasi total dokumen (2) dan total size (5MB)
        $response->assertInertia(fn (Assert $page) => $page
            ->where('reportData.global.overview_stats.total_documents', 2)
            ->where('reportData.global.overview_stats.total_size', 5 * 1024 * 1024)
            ->has('reportData.per_division', 2)
        );
    });
});
