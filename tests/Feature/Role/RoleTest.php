<?php

use App\Models\User;
use App\Enums\RoleRolePermission;
use Database\Seeders\InventoryModuleSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(InventoryModuleSeeder::class);
});

describe('Role & Permission Access Control', function () {
    /**
     * Memastikan user dengan izin 'lihat_role' dapat mengakses index, datatable, dan excel role.
     */
    it('allows users with lihat_role to access index, datatable, and print', function () {
        // 1. Persiapan user dengan izin lihat role
        $user = User::factory()->create();
        $user->givePermissionTo(RoleRolePermission::VIEW_ROLE->value);

        // 2. Validasi akses ke route role
        $this->actingAs($user)->get('/role')->assertStatus(200);
        $this->actingAs($user)->get('/role/datatable')->assertStatus(200);
        $this->actingAs($user)->get('/role/print/excel')->assertStatus(200);
    });

    /**
     * Memastikan user yang hanya memiliki izin 'lihat_role' tidak bisa mengakses route manajemen.
     */
    it('denies users with only lihat_role from accessing management routes', function () {
        // 1. Persiapan user dan role
        $user = User::factory()->create();
        $user->givePermissionTo(RoleRolePermission::VIEW_ROLE->value);
        $role = Role::create(['name' => 'Test Role']);

        // 2. Validasi penolakan (403) pada CRUD role
        $this->actingAs($user)->get('/role/create')->assertStatus(403);
        $this->actingAs($user)->post('/role/store', [])->assertStatus(403);
        $this->actingAs($user)->get("/role/{$role->id}/edit")->assertStatus(403);
        $this->actingAs($user)->put("/role/{$role->id}/update", [])->assertStatus(403);
        $this->actingAs($user)->delete("/role/{$role->id}/delete")->assertStatus(403);
    });

    /**
     * Memastikan user dengan izin 'kelola_role' diizinkan mengakses route manajemen.
     */
    it('allows users with kelola_role to access management routes', function () {
        // 1. Persiapan user dengan izin kelola role
        $user = User::factory()->create();
        $user->givePermissionTo(RoleRolePermission::MANAGE_ROLE->value);
        $role = Role::create(['name' => 'Test Role']);

        // 2. Validasi akses ke create dan edit
        $this->actingAs($user)->get('/role/create')->assertStatus(200);
        $this->actingAs($user)->get("/role/{$role->id}/edit")->assertStatus(200);
    });
});

describe('Role Datatable Features', function () {
    /**
     * Test fitur pencarian global dan pencarian spesifik kolom nama pada datatable role.
     */
    it('can perform global search and individual search on name', function () {
        // 1. Persiapan user dan data
        $user = User::factory()->create();
        $user->givePermissionTo(RoleRolePermission::VIEW_ROLE->value);
        Role::create(['name' => 'Developer Role']);
        Role::create(['name' => 'Manager Role']);

        // 2. Test Pencarian Global
        $response = $this->actingAs($user)->get('/role/datatable?search=Developer');
        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.name'))->toBe('Developer Role');

        // 3. Test filter kolom Nama
        $response = $this->actingAs($user)->get('/role/datatable?name=Manager');
        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.name'))->toBe('Manager Role');
    });

    /**
     * Test fitur limit dan pagination pada datatable role.
     */
    it('can paginate and limit results', function () {
        // 1. Persiapan user
        $user = User::factory()->create();
        $user->givePermissionTo(RoleRolePermission::VIEW_ROLE->value);

        // 2. Tambah banyak data role untuk pengetesan pagination
        for ($i = 0; $i < 10; $i++) {
            Role::create(['name' => "Role $i"]);
        }

        // 3. Request datatable dengan limit 5 halaman 2
        $response = $this->actingAs($user)->get('/role/datatable?limit=5&page=2');

        // 4. Validasi jumlah data dan halaman aktif
        $response->assertStatus(200);
        $json = $response->json();
        expect($json['data'])->toHaveCount(5);
        expect($json['current_page'])->toBe(2);
    });
});

describe('Role Print Functionality', function () {
    /**
     * Test export data role ke Excel.
     */
    it('returns xlsx file for printing roles', function () {
        // 1. Persiapan user
        $user = User::factory()->create();
        $user->givePermissionTo(RoleRolePermission::VIEW_ROLE->value);

        // 2. Request print excel
        $response = $this->actingAs($user)->get('/role/print/excel');

        // 3. Validasi response header excel
        $response->assertStatus(200)
            ->assertHeader('Content-Disposition', 'attachment; filename="Data Role Per ' . date('d F Y') . '.xlsx"');
    });
});
