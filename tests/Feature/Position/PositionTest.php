<?php

use App\Models\Position;
use App\Models\User;
use App\Enums\PositionRolePermission;
use Database\Seeders\InventoryModuleSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(InventoryModuleSeeder::class);
});

describe('Position Access Control', function () {
    /**
     * Memastikan user dengan izin 'lihat_jabatan' dapat mengakses index, datatable, dan excel jabatan.
     */
    it('allows users with lihat_jabatan to access index, datatable, and print', function () {
        // 1. Persiapan user dengan izin lihat jabatan
        $user = User::factory()->create();
        $user->givePermissionTo(PositionRolePermission::VIEW_POSITION->value);

        // 2. Validasi akses ke route jabatan
        $this->actingAs($user)->get('/position')->assertStatus(200);
        $this->actingAs($user)->get('/position/datatable')->assertStatus(200);
        $this->actingAs($user)->get('/position/print/excel')->assertStatus(200);
    });

    /**
     * Memastikan user yang hanya memiliki izin 'lihat_jabatan' ditolak saat mengakses route manajemen.
     */
    it('denies users with only lihat_jabatan from accessing management routes', function () {
        // 1. Persiapan user dan data jabatan
        $user = User::factory()->create();
        $user->givePermissionTo(PositionRolePermission::VIEW_POSITION->value);
        $position = Position::factory()->create();

        // 2. Validasi penolakan (403) pada CRUD
        $this->actingAs($user)->get('/position/create')->assertStatus(403);
        $this->actingAs($user)->post('/position/store', [])->assertStatus(403);
        $this->actingAs($user)->get("/position/{$position->id}/edit")->assertStatus(403);
        $this->actingAs($user)->put("/position/{$position->id}/update", [])->assertStatus(403);
        $this->actingAs($user)->delete("/position/{$position->id}/delete")->assertStatus(403);
    });

    /**
     * Memastikan user dengan izin 'kelola_jabatan' diizinkan mengakses route manajemen.
     */
    it('allows users with kelola_jabatan to access management routes', function () {
        // 1. Persiapan user dan data
        $user = User::factory()->create();
        $user->givePermissionTo(PositionRolePermission::MANAGE_POSITION->value);
        $position = Position::factory()->create();

        // 2. Validasi akses ke create dan edit
        $this->actingAs($user)->get('/position/create')->assertStatus(200);
        $this->actingAs($user)->get("/position/{$position->id}/edit")->assertStatus(200);
    });
});

describe('Position Datatable Features', function () {
    /**
     * Test fitur pencarian global berdasarkan nama jabatan pada datatable.
     */
    it('can search globally using name', function () {
        // 1. Persiapan user dan data jabatan
        $user = User::factory()->create();
        $user->givePermissionTo(PositionRolePermission::VIEW_POSITION->value);
        Position::factory()->create(['name' => 'Software Engineer']);
        Position::factory()->create(['name' => 'Product Manager']);

        // 2. Request datatable dengan parameter search
        $response = $this->actingAs($user)->get('/position/datatable?search=Software');

        // 3. Validasi hasil pencarian
        $response->assertStatus(200);
        $data = $response->json('data');
        expect($data)->toHaveCount(1);
        expect($data[0]['name'])->toBe('Software Engineer');
    });

    /**
     * Test fitur limit dan pagination pada datatable jabatan.
     */
    it('can filter results with limit and page', function () {
        // 1. Persiapan user dan data
        $user = User::factory()->create();
        $user->givePermissionTo(PositionRolePermission::VIEW_POSITION->value);
        Position::factory()->count(15)->create();

        // 2. Request datatable dengan limit 5 halaman 2
        $response = $this->actingAs($user)->get('/position/datatable?limit=5&page=2');

        // 3. Validasi jumlah data dan pagination
        $response->assertStatus(200);
        $json = $response->json();
        expect($json['data'])->toHaveCount(5);
        expect($json['total'])->toBe(15);
        expect($json['current_page'])->toBe(2);
    });

    /**
     * Test pencarian spesifik pada kolom Nama Jabatan.
     */
    it('can perform individual search on name column', function () {
        // 1. Persiapan user dan data
        $user = User::factory()->create();
        $user->givePermissionTo(PositionRolePermission::VIEW_POSITION->value);
        Position::factory()->create(['name' => 'Director']);
        Position::factory()->create(['name' => 'Manager']);

        // 2. Request filter nama
        $response = $this->actingAs($user)->get('/position/datatable?name=Director');

        // 3. Validasi hasil filter
        $response->assertStatus(200);
        $data = $response->json('data');
        expect($data)->toHaveCount(1);
        expect($data[0]['name'])->toBe('Director');
    });

    /**
     * Test pencarian spesifik pada kolom Deskripsi Jabatan.
     */
    it('can perform individual search on description column', function () {
        // 1. Persiapan user dan data
        $user = User::factory()->create();
        $user->givePermissionTo(PositionRolePermission::VIEW_POSITION->value);
        Position::factory()->create(['description' => 'Handle code and stuff']);
        Position::factory()->create(['description' => 'Handle people and stuff']);

        // 2. Request filter deskripsi
        $response = $this->actingAs($user)->get('/position/datatable?description=code');

        // 3. Validasi hasil filter
        $response->assertStatus(200);
        $data = $response->json('data');
        expect($data)->toHaveCount(1);
        expect($data[0]['description'])->toContain('code');
    });

    /**
     * Test filter berdasarkan status aktif (is_active) jabatan.
     */
    it('can perform individual search on is_active status', function () {
        // 1. Persiapan user dan data
        $user = User::factory()->create();
        $user->givePermissionTo(PositionRolePermission::VIEW_POSITION->value);
        Position::factory()->create(['is_active' => true, 'name' => 'Active Pos']);
        Position::factory()->create(['is_active' => false, 'name' => 'Inactive Pos']);

        // 2. Test filter status Aktif
        $responseActive = $this->actingAs($user)->get('/position/datatable?is_active=1');
        expect($responseActive->json('data'))->toHaveCount(1);
        expect($responseActive->json('data.0.is_active'))->toBeTrue();

        // 3. Test filter status Tidak Aktif
        $responseInactive = $this->actingAs($user)->get('/position/datatable?is_active=0');
        expect($responseInactive->json('data'))->toHaveCount(1);
        expect($responseInactive->json('data.0.is_active'))->toBeFalse();
    });

    /**
     * Test filter berdasarkan jumlah user yang menduduki jabatan tersebut (users_count).
     */
    it('can perform individual search on users_count', function () {
        // 1. Persiapan user penguji dan jabatan dengan jumlah personil berbeda
        $user = User::factory()->create();
        $user->givePermissionTo(PositionRolePermission::VIEW_POSITION->value);

        $pos1 = Position::factory()->create();
        User::factory()->count(3)->create(['position_id' => $pos1->id]);

        $pos2 = Position::factory()->create();
        User::factory()->count(1)->create(['position_id' => $pos2->id]);

        // 2. Request filter jumlah user = 3
        $response = $this->actingAs($user)->get('/position/datatable?users_count=3');

        // 3. Validasi jabatan yang terpilih
        $response->assertStatus(200);
        $data = $response->json('data');
        expect($data)->toHaveCount(1);
        expect($data[0]['id'])->toBe($pos1->id);
    });
});

describe('Position Print functionality', function () {
    /**
     * Test export data jabatan ke Excel.
     */
    it('returns xlsx file for printing', function () {
        // 1. Persiapan user dan data
        $user = User::factory()->create();
        $user->givePermissionTo(PositionRolePermission::VIEW_POSITION->value);
        Position::factory()->count(5)->create();

        // 2. Validasi response export
        $response = $this->actingAs($user)->get('/position/print/excel');
        $response->assertStatus(200)
            ->assertHeader('Content-Disposition', 'attachment; filename="Data Jabatan Per ' . date('d F Y') . '.xlsx"');
    });

    /**
     * Memastikan filter pencarian tetap berfungsi saat melakukan export Excel.
     */
    it('respects filters when printing to excel', function () {
        // 1. Persiapan user dan data
        $user = User::factory()->create();
        $user->givePermissionTo(PositionRolePermission::VIEW_POSITION->value);
        Position::factory()->create(['name' => 'Software Engineer']);
        Position::factory()->create(['name' => 'Product Manager']);

        // 2. Request export dengan filter pencarian
        $response = $this->actingAs($user)->get('/position/print/excel?search=Software');

        // 3. Validasi sukses
        $response->assertStatus(200);
    });
});
