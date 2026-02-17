<?php

use App\Models\Division;
use App\Models\User;
use App\Enums\DivisionRolePermission;
use Database\Seeders\InventoryModuleSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(InventoryModuleSeeder::class);
});

describe('Division Access Control', function () {
    /**
     * Memastikan user dengan izin 'lihat_divisi' dapat mengakses halaman utama, datatable, dan export.
     */
    it('allows users with lihat_divisi to access index, datatable, and print', function () {
        // 1. Persiapan user dengan izin lihat divisi
        $user = User::factory()->create();
        $user->givePermissionTo(DivisionRolePermission::VIEW_DIVISION->value);

        // 2. Validasi akses ke index, datatable, dan excel
        $this->actingAs($user)->get('/division')->assertStatus(200);
        $this->actingAs($user)->get('/division/datatable')->assertStatus(200);
        $this->actingAs($user)->get('/division/print/excel')->assertStatus(200);
    });

    /**
     * Memastikan user yang hanya memiliki izin 'lihat_divisi' tidak bisa mengakses route manajemen (Create, Store, Edit, Update, Delete).
     */
    it('denies users with only lihat_divisi from accessing management routes', function () {
        // 1. Persiapan user dan data divisi
        $user = User::factory()->create();
        $user->givePermissionTo(DivisionRolePermission::VIEW_DIVISION->value);
        $division = Division::factory()->create();

        // 2. Validasi penolakan (403) pada semua route manajemen
        $this->actingAs($user)->get('/division/create')->assertStatus(403);
        $this->actingAs($user)->post('/division/store', [])->assertStatus(403);
        $this->actingAs($user)->get("/division/{$division->id}/edit")->assertStatus(403);
        $this->actingAs($user)->put("/division/{$division->id}/update", [])->assertStatus(403);
        $this->actingAs($user)->delete("/division/{$division->id}/delete")->assertStatus(403);
    });

    /**
     * Memastikan user dengan izin 'kelola_divisi' diizinkan mengakses route manajemen.
     */
    it('allows users with kelola_divisi to access management routes', function () {
        // 1. Persiapan user dengan izin kelola divisi
        $user = User::factory()->create();
        $user->givePermissionTo(DivisionRolePermission::MANAGE_DIVISION->value);
        $division = Division::factory()->create();

        // 2. Validasi akses ke halaman create dan edit
        $this->actingAs($user)->get('/division/create')->assertStatus(200);
        $this->actingAs($user)->get("/division/{$division->id}/edit")->assertStatus(200);
    });
});

describe('Division Datatable Features', function () {
    /**
     * Test fitur pencarian global berdasarkan nama divisi pada datatable.
     */
    it('can search globally using name', function () {
        // 1. Persiapan user dan data beberapa divisi
        $user = User::factory()->create();
        $user->givePermissionTo(DivisionRolePermission::VIEW_DIVISION->value);
        Division::factory()->create(['name' => 'IT Department']);
        Division::factory()->create(['name' => 'HR Department']);

        // 2. Request datatable dengan parameter search
        $response = $this->actingAs($user)->get('/division/datatable?search=IT');

        // 3. Validasi hasil pencarian
        $response->assertStatus(200);
        $data = $response->json('data');
        expect($data)->toHaveCount(1);
        expect($data[0]['name'])->toBe('IT Department');
    });

    /**
     * Test fitur limit dan pagination pada datatable divisi.
     */
    it('can filter results with limit and page', function () {
        // 1. Persiapan user dan banyak data divisi
        $user = User::factory()->create();
        $user->givePermissionTo(DivisionRolePermission::VIEW_DIVISION->value);
        Division::factory()->count(15)->create();

        // 2. Request datatable dengan limit 5 ke halaman 2
        $response = $this->actingAs($user)->get('/division/datatable?limit=5&page=2');

        // 3. Validasi pagination dan jumlah data
        $response->assertStatus(200);
        $json = $response->json();
        expect($json['data'])->toHaveCount(5);
        expect($json['total'])->toBe(15);
        expect($json['current_page'])->toBe(2);
    });

    /**
     * Test fitur pencarian spesifik pada kolom Nama.
     */
    it('can perform individual search on name column', function () {
        // 1. Persiapan user dan data
        $user = User::factory()->create();
        $user->givePermissionTo(DivisionRolePermission::VIEW_DIVISION->value);
        Division::factory()->create(['name' => 'Finance']);
        Division::factory()->create(['name' => 'Marketing']);

        // 2. Request datatable dengan filter kolom nama
        $response = $this->actingAs($user)->get('/division/datatable?name=Finance');

        // 3. Validasi hasil filter
        $response->assertStatus(200);
        $data = $response->json('data');
        expect($data)->toHaveCount(1);
        expect($data[0]['name'])->toBe('Finance');
    });

    /**
     * Test fitur pencarian spesifik pada kolom Deskripsi.
     */
    it('can perform individual search on description column', function () {
        // 1. Persiapan user dan data
        $user = User::factory()->create();
        $user->givePermissionTo(DivisionRolePermission::VIEW_DIVISION->value);
        Division::factory()->create(['description' => 'Handle technology stuff']);
        Division::factory()->create(['description' => 'Handle human resources']);

        // 2. Request datatable dengan filter kolom deskripsi
        $response = $this->actingAs($user)->get('/division/datatable?description=technology');

        // 3. Validasi hasil filter
        $response->assertStatus(200);
        $data = $response->json('data');
        expect($data)->toHaveCount(1);
        expect($data[0]['description'])->toContain('technology');
    });

    /**
     * Test fitur pencarian spesifik pada kolom status aktif (is_active).
     */
    it('can perform individual search on is_active status', function () {
        // 1. Persiapan user dan data (aktif & tidak aktif)
        $user = User::factory()->create();
        $user->givePermissionTo(DivisionRolePermission::VIEW_DIVISION->value);
        Division::factory()->create(['is_active' => true, 'name' => 'Active Div']);
        Division::factory()->create(['is_active' => false, 'name' => 'Inactive Div']);

        // 2. Test filter status Aktif (is_active=1)
        $responseActive = $this->actingAs($user)->get('/division/datatable?is_active=1');
        expect($responseActive->json('data'))->toHaveCount(1);
        expect($responseActive->json('data.0.is_active'))->toBeTrue();

        // 3. Test filter status Tidak Aktif (is_active=0)
        $responseInactive = $this->actingAs($user)->get('/division/datatable?is_active=0');
        expect($responseInactive->json('data'))->toHaveCount(1);
        expect($responseInactive->json('data.0.is_active'))->toBeFalse();
    });

    /**
     * Test fitur pencarian spesifik berdasarkan jumlah user (users_count).
     */
    it('can perform individual search on users_count', function () {
        // 1. Persiapan user penguji dan 2 divisi dengan jumlah anggota berbeda
        $user = User::factory()->create();
        $user->givePermissionTo(DivisionRolePermission::VIEW_DIVISION->value);

        $div1 = Division::factory()->create();
        User::factory()->count(3)->create(['division_id' => $div1->id]);

        $div2 = Division::factory()->create();
        User::factory()->count(1)->create(['division_id' => $div2->id]);

        // 2. Request datatable dengan filter jumlah user = 3
        $response = $this->actingAs($user)->get('/division/datatable?users_count=3');

        // 3. Validasi hasil filter menampilkan divisi yang benar
        $response->assertStatus(200);
        $data = $response->json('data');
        expect($data)->toHaveCount(1);
        expect($data[0]['id'])->toBe($div1->id);
    });
});

describe('Division Print functionality', function () {
    /**
     * Test fitur export data divisi ke excel.
     */
    it('returns xlsx file for printing', function () {
        // 1. Persiapan user dan data
        $user = User::factory()->create();
        $user->givePermissionTo(DivisionRolePermission::VIEW_DIVISION->value);
        Division::factory()->count(5)->create();

        // 2. Validasi response export excel
        $response = $this->actingAs($user)->get('/division/print/excel');
        $response->assertStatus(200)
            ->assertHeader('Content-Disposition', 'attachment; filename="Data Divisi Per ' . date('d F Y') . '.xlsx"');
    });

    /**
     * Memastikan filter pencarian tetap berlaku saat melakukan export excel.
     */
    it('respects filters when printing to excel', function () {
        // 1. Persiapan user dan data pencarian
        $user = User::factory()->create();
        $user->givePermissionTo(DivisionRolePermission::VIEW_DIVISION->value);
        Division::factory()->create(['name' => 'IT Department']);
        Division::factory()->create(['name' => 'HR Department']);

        // 2. Request export dengan parameter pencarian
        $response = $this->actingAs($user)->get('/division/print/excel?search=IT');

        // 3. Validasi response sukses
        $response->assertStatus(200);
    });
});

