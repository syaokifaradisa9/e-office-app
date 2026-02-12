<?php

use App\Models\Division;
use App\Models\Position;
use App\Models\User;
use App\Enums\UserRolePermission;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

describe('User Management Access Control', function () {
    it('allows users with lihat_pengguna to access index, datatable, and print', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(UserRolePermission::VIEW_USER->value);

        $this->actingAs($user)->get('/user')->assertStatus(200);
        $this->actingAs($user)->get('/user/datatable')->assertStatus(200);
        $this->actingAs($user)->get('/user/print/excel')->assertStatus(200);
    });

    it('denies users with only lihat_pengguna from accessing management routes', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(UserRolePermission::VIEW_USER->value);

        $targetUser = User::factory()->create();

        $this->actingAs($user)->get('/user/create')->assertStatus(403);
        $this->actingAs($user)->post('/user/store', [])->assertStatus(403);
        $this->actingAs($user)->get("/user/{$targetUser->id}/edit")->assertStatus(403);
        $this->actingAs($user)->put("/user/{$targetUser->id}/update", [])->assertStatus(403);
        $this->actingAs($user)->delete("/user/{$targetUser->id}/delete")->assertStatus(403);
    });

    it('allows users with kelola_pengguna to access management routes', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(UserRolePermission::MANAGE_USER->value);

        $targetUser = User::factory()->create();

        $this->actingAs($user)->get('/user/create')->assertStatus(200);
        $this->actingAs($user)->get("/user/{$targetUser->id}/edit")->assertStatus(200);
    });
});

describe('User Datatable Features', function () {
    it('can search globally using name or email', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(UserRolePermission::VIEW_USER->value);

        User::factory()->create(['name' => 'John Doe', 'email' => 'john@test.com']);
        User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@test.com']);

        // Search by name
        $response = $this->actingAs($user)->get('/user/datatable?search=John');
        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.name'))->toBe('John Doe');

        // Search by email
        $response = $this->actingAs($user)->get('/user/datatable?search=jane@test');
        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.email'))->toBe('jane@test.com');
    });

    it('can filter results with limit and page', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(UserRolePermission::VIEW_USER->value);

        User::factory()->count(15)->create();

        $response = $this->actingAs($user)->get('/user/datatable?limit=5&page=2');

        $response->assertStatus(200);
        $json = $response->json();
        expect($json['data'])->toHaveCount(5);
        expect($json['total'])->toBe(16); // 15 + 1 logged user
        expect($json['current_page'])->toBe(2);
    });

    it('can perform individual search on name column', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(UserRolePermission::VIEW_USER->value);

        User::factory()->create(['name' => 'Specific User']);
        User::factory()->create(['name' => 'Other User']);

        $response = $this->actingAs($user)->get('/user/datatable?name=Specific');

        $response->assertStatus(200);
        $data = $response->json('data');
        expect($data)->toHaveCount(1);
        expect($data[0]['name'])->toBe('Specific User');
    });

    it('can perform individual search on email column', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(UserRolePermission::VIEW_USER->value);

        User::factory()->create(['email' => 'specific@test.com']);
        User::factory()->create(['email' => 'other@test.com']);

        $response = $this->actingAs($user)->get('/user/datatable?email=specific@test');

        $response->assertStatus(200);
        $data = $response->json('data');
        expect($data)->toHaveCount(1);
        expect($data[0]['email'])->toBe('specific@test.com');
    });

    it('can filter by division_id', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(UserRolePermission::VIEW_USER->value);

        $div1 = Division::factory()->create();
        $div2 = Division::factory()->create();

        User::factory()->create(['division_id' => $div1->id]);
        User::factory()->create(['division_id' => $div2->id]);

        $response = $this->actingAs($user)->get("/user/datatable?division_id={$div1->id}");

        $response->assertStatus(200);
        foreach ($response->json('data') as $row) {
            expect($row['division_id'])->toBe($div1->id);
        }
    });

    it('can filter by position_id', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(UserRolePermission::VIEW_USER->value);

        $pos1 = Position::factory()->create();
        $pos2 = Position::factory()->create();

        User::factory()->create(['position_id' => $pos1->id]);
        User::factory()->create(['position_id' => $pos2->id]);

        $response = $this->actingAs($user)->get("/user/datatable?position_id={$pos1->id}");

        $response->assertStatus(200);
        foreach ($response->json('data') as $row) {
            expect($row['position_id'])->toBe($pos1->id);
        }
    });

    it('can perform individual search on is_active status', function () {
        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo(UserRolePermission::VIEW_USER->value);

        User::factory()->create(['is_active' => false, 'name' => 'Inactive User']);

        // Test inactive
        $responseInactive = $this->actingAs($user)->get('/user/datatable?is_active=0');
        expect($responseInactive->json('data'))->toHaveCount(1);
        expect($responseInactive->json('data.0.is_active'))->toBeFalse();
    });
});

describe('User Print functionality', function () {
    it('returns xlsx file for printing', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(UserRolePermission::VIEW_USER->value);

        User::factory()->count(5)->create();

        $response = $this->actingAs($user)->get('/user/print/excel');

        $response->assertStatus(200)
            ->assertHeader('Content-Disposition', 'attachment; filename="Data Pengguna Per ' . date('d F Y') . '.xlsx"');
    });

    it('respects filters when printing to excel', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(UserRolePermission::VIEW_USER->value);

        User::factory()->create(['name' => 'Excel User']);
        User::factory()->create(['name' => 'Other User']);

        $response = $this->actingAs($user)->get('/user/print/excel?search=Excel');

        $response->assertStatus(200);
    });
});
