<?php

use App\Models\Position;
use App\Models\User;
use App\Enums\PositionRolePermission;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

describe('Position Access Control', function () {
    it('allows users with lihat_jabatan to access index, datatable, and print', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(PositionRolePermission::VIEW_POSITION->value);

        $this->actingAs($user)->get('/position')->assertStatus(200);
        $this->actingAs($user)->get('/position/datatable')->assertStatus(200);
        $this->actingAs($user)->get('/position/print/excel')->assertStatus(200);
    });

    it('denies users with only lihat_jabatan from accessing management routes', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(PositionRolePermission::VIEW_POSITION->value);

        $position = Position::factory()->create();

        $this->actingAs($user)->get('/position/create')->assertStatus(403);
        $this->actingAs($user)->post('/position/store', [])->assertStatus(403);
        $this->actingAs($user)->get("/position/{$position->id}/edit")->assertStatus(403);
        $this->actingAs($user)->put("/position/{$position->id}/update", [])->assertStatus(403);
        $this->actingAs($user)->delete("/position/{$position->id}/delete")->assertStatus(403);
    });

    it('allows users with kelola_jabatan to access management routes', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(PositionRolePermission::MANAGE_POSITION->value);

        $position = Position::factory()->create();

        $this->actingAs($user)->get('/position/create')->assertStatus(200);
        $this->actingAs($user)->get("/position/{$position->id}/edit")->assertStatus(200);
    });
});

describe('Position Datatable Features', function () {
    it('can search globally using name', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(PositionRolePermission::VIEW_POSITION->value);

        Position::factory()->create(['name' => 'Software Engineer']);
        Position::factory()->create(['name' => 'Product Manager']);

        $response = $this->actingAs($user)->get('/position/datatable?search=Software');

        $response->assertStatus(200);
        $data = $response->json('data');
        expect($data)->toHaveCount(1);
        expect($data[0]['name'])->toBe('Software Engineer');
    });

    it('can filter results with limit and page', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(PositionRolePermission::VIEW_POSITION->value);

        Position::factory()->count(15)->create();

        $response = $this->actingAs($user)->get('/position/datatable?limit=5&page=2');

        $response->assertStatus(200);
        $json = $response->json();
        expect($json['data'])->toHaveCount(5);
        expect($json['total'])->toBe(15);
        expect($json['current_page'])->toBe(2);
    });

    it('can perform individual search on name column', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(PositionRolePermission::VIEW_POSITION->value);

        Position::factory()->create(['name' => 'Director']);
        Position::factory()->create(['name' => 'Manager']);

        $response = $this->actingAs($user)->get('/position/datatable?name=Director');

        $response->assertStatus(200);
        $data = $response->json('data');
        expect($data)->toHaveCount(1);
        expect($data[0]['name'])->toBe('Director');
    });

    it('can perform individual search on description column', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(PositionRolePermission::VIEW_POSITION->value);

        Position::factory()->create(['description' => 'Handle code and stuff']);
        Position::factory()->create(['description' => 'Handle people and stuff']);

        $response = $this->actingAs($user)->get('/position/datatable?description=code');

        $response->assertStatus(200);
        $data = $response->json('data');
        expect($data)->toHaveCount(1);
        expect($data[0]['description'])->toContain('code');
    });

    it('can perform individual search on is_active status', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(PositionRolePermission::VIEW_POSITION->value);

        Position::factory()->create(['is_active' => true, 'name' => 'Active Pos']);
        Position::factory()->create(['is_active' => false, 'name' => 'Inactive Pos']);

        // Test active
        $responseActive = $this->actingAs($user)->get('/position/datatable?is_active=1');
        expect($responseActive->json('data'))->toHaveCount(1);
        expect($responseActive->json('data.0.is_active'))->toBeTrue();

        // Test inactive
        $responseInactive = $this->actingAs($user)->get('/position/datatable?is_active=0');
        expect($responseInactive->json('data'))->toHaveCount(1);
        expect($responseInactive->json('data.0.is_active'))->toBeFalse();
    });

    it('can perform individual search on users_count', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(PositionRolePermission::VIEW_POSITION->value);

        $pos1 = Position::factory()->create();
        User::factory()->count(3)->create(['position_id' => $pos1->id]);

        $pos2 = Position::factory()->create();
        User::factory()->count(1)->create(['position_id' => $pos2->id]);

        $response = $this->actingAs($user)->get('/position/datatable?users_count=3');

        $response->assertStatus(200);
        $data = $response->json('data');
        expect($data)->toHaveCount(1);
        expect($data[0]['id'])->toBe($pos1->id);
    });
});

describe('Position Print functionality', function () {
    it('returns xlsx file for printing', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(PositionRolePermission::VIEW_POSITION->value);

        Position::factory()->count(5)->create();

        $response = $this->actingAs($user)->get('/position/print/excel');

        $response->assertStatus(200)
            ->assertHeader('Content-Disposition', 'attachment; filename="Data Jabatan Per ' . date('d F Y') . '.xlsx"');
    });

    it('respects filters when printing to excel', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(PositionRolePermission::VIEW_POSITION->value);

        Position::factory()->create(['name' => 'Software Engineer']);
        Position::factory()->create(['name' => 'Product Manager']);

        $response = $this->actingAs($user)->get('/position/print/excel?search=Software');

        $response->assertStatus(200);
    });
});
