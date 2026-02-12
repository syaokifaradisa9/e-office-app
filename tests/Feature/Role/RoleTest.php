<?php

use App\Models\User;
use App\Enums\RoleRolePermission;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

describe('Role & Permission Access Control', function () {
    it('allows users with lihat_role to access index, datatable, and print', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(RoleRolePermission::VIEW_ROLE->value);

        $this->actingAs($user)->get('/role')->assertStatus(200);
        $this->actingAs($user)->get('/role/datatable')->assertStatus(200);
        $this->actingAs($user)->get('/role/print/excel')->assertStatus(200);
    });

    it('denies users with only lihat_role from accessing management routes', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(RoleRolePermission::VIEW_ROLE->value);

        $role = Role::create(['name' => 'Test Role']);

        $this->actingAs($user)->get('/role/create')->assertStatus(403);
        $this->actingAs($user)->post('/role/store', [])->assertStatus(403);
        $this->actingAs($user)->get("/role/{$role->id}/edit")->assertStatus(403);
        $this->actingAs($user)->put("/role/{$role->id}/update", [])->assertStatus(403);
        $this->actingAs($user)->delete("/role/{$role->id}/delete")->assertStatus(403);
    });

    it('allows users with kelola_role to access management routes', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(RoleRolePermission::MANAGE_ROLE->value);

        $role = Role::create(['name' => 'Test Role']);

        $this->actingAs($user)->get('/role/create')->assertStatus(200);
        $this->actingAs($user)->get("/role/{$role->id}/edit")->assertStatus(200);
    });
});

describe('Role Datatable Features', function () {
    it('can perform global search and individual search on name', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(RoleRolePermission::VIEW_ROLE->value);

        Role::create(['name' => 'Developer Role']);
        Role::create(['name' => 'Manager Role']);

        // Global search
        $response = $this->actingAs($user)->get('/role/datatable?search=Developer');
        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.name'))->toBe('Developer Role');

        // Individual column search
        $response = $this->actingAs($user)->get('/role/datatable?name=Manager');
        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.name'))->toBe('Manager Role');
    });

    it('can paginate and limit results', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(RoleRolePermission::VIEW_ROLE->value);

        // RoleSeeder already creates some roles (Superadmin, Admin, User, etc)
        // Let's create more to test pagination
        for ($i = 0; $i < 10; $i++) {
            Role::create(['name' => "Role $i"]);
        }

        $response = $this->actingAs($user)->get('/role/datatable?limit=5&page=2');

        $response->assertStatus(200);
        $json = $response->json();
        expect($json['data'])->toHaveCount(5);
        expect($json['current_page'])->toBe(2);
    });
});

describe('Role Print Functionality', function () {
    it('returns xlsx file for printing roles', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(RoleRolePermission::VIEW_ROLE->value);

        $response = $this->actingAs($user)->get('/role/print/excel');

        $response->assertStatus(200)
            ->assertHeader('Content-Disposition', 'attachment; filename="Data Role Per ' . date('d F Y') . '.xlsx"');
    });
});
