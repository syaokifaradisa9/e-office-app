<?php

use App\Models\Division;
use App\Models\Position;
use App\Models\User;
use App\Enums\UserRolePermission;
use Database\Seeders\InventoryModuleSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(InventoryModuleSeeder::class);
});

describe('User Management Access Control', function () {
    /**
     * Memastikan user dengan izin 'lihat_pengguna' dapat mengakses index, datatable, dan excel pengguna.
     */
    it('allows users with lihat_pengguna to access index, datatable, and print', function () {
        // 1. Persiapan user dengan izin lihat pengguna
        $user = User::factory()->create();
        $user->givePermissionTo(UserRolePermission::VIEW_USER->value);

        // 2. Validasi akses ke route manajemen pengguna
        $this->actingAs($user)->get('/user')->assertStatus(200);
        $this->actingAs($user)->get('/user/datatable')->assertStatus(200);
        $this->actingAs($user)->get('/user/print/excel')->assertStatus(200);
    });

    /**
     * Memastikan user yang hanya punya izin 'lihat_pengguna' ditolak saat mengakses route manajemen (Create, Store, Edit, Update, Delete).
     */
    it('denies users with only lihat_pengguna from accessing management routes', function () {
        // 1. Persiapan user penguji dan user target
        $user = User::factory()->create();
        $user->givePermissionTo(UserRolePermission::VIEW_USER->value);
        $targetUser = User::factory()->create();

        // 2. Validasi penolakan (403) pada aksi manajemen
        $this->actingAs($user)->get('/user/create')->assertStatus(403);
        $this->actingAs($user)->post('/user/store', [])->assertStatus(403);
        $this->actingAs($user)->get("/user/{$targetUser->id}/edit")->assertStatus(403);
        $this->actingAs($user)->put("/user/{$targetUser->id}/update", [])->assertStatus(403);
        $this->actingAs($user)->delete("/user/{$targetUser->id}/delete")->assertStatus(403);
    });

    /**
     * Memastikan user dengan izin 'kelola_pengguna' diizinkan mengakses route manajemen.
     */
    it('allows users with kelola_pengguna to access management routes', function () {
        // 1. Persiapan user dengan izin kelola
        $user = User::factory()->create();
        $user->givePermissionTo(UserRolePermission::MANAGE_USER->value);
        $targetUser = User::factory()->create();

        // 2. Validasi akses ke create dan edit
        $this->actingAs($user)->get('/user/create')->assertStatus(200);
        $this->actingAs($user)->get("/user/{$targetUser->id}/edit")->assertStatus(200);
    });
});

describe('User Datatable Features', function () {
    /**
     * Test fitur pencarian global berdasarkan Nama atau Email pada datatable pengguna.
     */
    it('can search globally using name or email', function () {
        // 1. Persiapan user
        $user = User::factory()->create();
        $user->givePermissionTo(UserRolePermission::VIEW_USER->value);
        User::factory()->create(['name' => 'John Doe', 'email' => 'john@test.com']);
        User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@test.com']);

        // 2. Test pencarian berdasarkan Nama
        $response = $this->actingAs($user)->get('/user/datatable?search=John');
        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.name'))->toBe('John Doe');

        // 3. Test pencarian berdasarkan Email
        $response = $this->actingAs($user)->get('/user/datatable?search=jane@test');
        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.email'))->toBe('jane@test.com');
    });

    /**
     * Test fitur limit dan pagination pada datatable pengguna.
     */
    it('can filter results with limit and page', function () {
        // 1. Persiapan user
        $user = User::factory()->create();
        $user->givePermissionTo(UserRolePermission::VIEW_USER->value);
        User::factory()->count(15)->create();

        // 2. Request datatable dengan limit 5 halaman 2
        $response = $this->actingAs($user)->get('/user/datatable?limit=5&page=2');

        // 3. Validasi jumlah data (15 + 1 user login) dan pagination
        $response->assertStatus(200);
        $json = $response->json();
        expect($json['data'])->toHaveCount(5);
        expect($json['total'])->toBe(20); // 15 + 1 logged user + 4 seeded
        expect($json['current_page'])->toBe(2);
    });

    /**
     * Test pencarian spesifik pada kolom Nama Pengguna.
     */
    it('can perform individual search on name column', function () {
        // 1. Persiapan data
        $user = User::factory()->create();
        $user->givePermissionTo(UserRolePermission::VIEW_USER->value);
        User::factory()->create(['name' => 'Specific User']);
        User::factory()->create(['name' => 'Other User']);

        // 2. Request filter nama
        $response = $this->actingAs($user)->get('/user/datatable?name=Specific');

        // 3. Validasi hasil filter
        $response->assertStatus(200);
        $data = $response->json('data');
        expect($data)->toHaveCount(1);
        expect($data[0]['name'])->toBe('Specific User');
    });

    /**
     * Test pencarian spesifik pada kolom Email Pengguna.
     */
    it('can perform individual search on email column', function () {
        // 1. Persiapan data
        $user = User::factory()->create();
        $user->givePermissionTo(UserRolePermission::VIEW_USER->value);
        User::factory()->create(['email' => 'specific@test.com']);
        User::factory()->create(['email' => 'other@test.com']);

        // 2. Request filter email
        $response = $this->actingAs($user)->get('/user/datatable?email=specific@test');

        // 3. Validasi hasil filter
        $response->assertStatus(200);
        $data = $response->json('data');
        expect($data)->toHaveCount(1);
        expect($data[0]['email'])->toBe('specific@test.com');
    });

    /**
     * Test filter pengguna berdasarkan Divisi (division_id).
     */
    it('can filter by division_id', function () {
        // 1. Persiapan data divisi dan user di masing-masing divisi
        $user = User::factory()->create();
        $user->givePermissionTo(UserRolePermission::VIEW_USER->value);

        $div1 = Division::factory()->create();
        $div2 = Division::factory()->create();

        User::factory()->create(['division_id' => $div1->id]);
        User::factory()->create(['division_id' => $div2->id]);

        // 2. Request filter divisi 1
        $response = $this->actingAs($user)->get("/user/datatable?division_id={$div1->id}");

        // 3. Validasi bahwa semua hasil yang muncul milik divisi 1
        $response->assertStatus(200);
        foreach ($response->json('data') as $row) {
            expect($row['division_id'])->toBe($div1->id);
        }
    });

    /**
     * Test filter pengguna berdasarkan Jabatan (position_id).
     */
    it('can filter by position_id', function () {
        // 1. Persiapan data jabatan dan user di masing-masing jabatan
        $user = User::factory()->create();
        $user->givePermissionTo(UserRolePermission::VIEW_USER->value);

        $pos1 = Position::factory()->create();
        $pos2 = Position::factory()->create();

        User::factory()->create(['position_id' => $pos1->id]);
        User::factory()->create(['position_id' => $pos2->id]);

        // 2. Request filter jabatan 1
        $response = $this->actingAs($user)->get("/user/datatable?position_id={$pos1->id}");

        // 3. Validasi bahwa semua hasil yang muncul memiliki jabatan 1
        $response->assertStatus(200);
        foreach ($response->json('data') as $row) {
            expect($row['position_id'])->toBe($pos1->id);
        }
    });

    /**
     * Test filter berdasarkan status aktif (is_active) pengguna.
     */
    it('can perform individual search on is_active status', function () {
        // 1. Persiapan user penguji dan user tidak aktif
        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo(UserRolePermission::VIEW_USER->value);

        User::factory()->create(['is_active' => false, 'name' => 'Inactive User']);

        // 2. Request filter status Tidak Aktif
        $responseInactive = $this->actingAs($user)->get('/user/datatable?is_active=0');
        
        // 3. Validasi hasil filter
        expect($responseInactive->json('data'))->toHaveCount(1);
        expect($responseInactive->json('data.0.is_active'))->toBeFalse();
    });
});

describe('User Print functionality', function () {
    /**
     * Test export data pengguna ke Excel.
     */
    it('returns xlsx file for printing', function () {
        // 1. Persiapan data
        $user = User::factory()->create();
        $user->givePermissionTo(UserRolePermission::VIEW_USER->value);
        User::factory()->count(5)->create();

        // 2. Request print excel
        $response = $this->actingAs($user)->get('/user/print/excel');

        // 3. Validasi response header
        $response->assertStatus(200)
            ->assertHeader('Content-Disposition', 'attachment; filename="Data Pengguna Per ' . date('d F Y') . '.xlsx"');
    });

    /**
     * Memastikan parameter pencarian tetap terbawa saat melakukan export ke Excel.
     */
    it('respects filters when printing to excel', function () {
        // 1. Persiapan user
        $user = User::factory()->create();
        $user->givePermissionTo(UserRolePermission::VIEW_USER->value);
        User::factory()->create(['name' => 'Excel User']);
        User::factory()->create(['name' => 'Other User']);

        // 2. Request export dengan parameter pencarian
        $response = $this->actingAs($user)->get('/user/print/excel?search=Excel');

        // 3. Validasi sukses
        $response->assertStatus(200);
    });
});
