<?php

use App\Models\Position;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

it('can display position index page', function () {
    $user = User::factory()->create();
    $user->assignRole('Superadmin');

    $response = $this->actingAs($user)->get('/position');

    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Position/Index'));
});

it('can display position create page', function () {
    $user = User::factory()->create();
    $user->assignRole('Superadmin');

    $response = $this->actingAs($user)->get('/position/create');

    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Position/Create'));
});

it('can create a new position', function () {
    $user = User::factory()->create();
    $user->assignRole('Superadmin');

    $response = $this->actingAs($user)->post('/position/store', [
        'name' => 'Test Position',
        'description' => 'Test Description',
        'is_active' => true,
    ]);

    $response->assertRedirect('/position');
    $this->assertDatabaseHas('positions', [
        'name' => 'Test Position',
        'description' => 'Test Description',
    ]);
});

it('requires name when creating position', function () {
    $user = User::factory()->create();
    $user->assignRole('Superadmin');

    $response = $this->actingAs($user)->post('/position/store', [
        'description' => 'Test Description',
    ]);

    $response->assertSessionHasErrors('name');
});

it('can display position edit page', function () {
    $user = User::factory()->create();
    $user->assignRole('Superadmin');

    $position = Position::factory()->create();

    $response = $this->actingAs($user)->get("/position/{$position->id}/edit");

    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Position/Create')
            ->has('position'));
});

it('can update an existing position', function () {
    $user = User::factory()->create();
    $user->assignRole('Superadmin');

    $position = Position::factory()->create([
        'name' => 'Old Name',
    ]);

    $response = $this->actingAs($user)->put("/position/{$position->id}/update", [
        'name' => 'New Name',
        'description' => 'Updated Description',
        'is_active' => true,
    ]);

    $response->assertRedirect('/position');
    $this->assertDatabaseHas('positions', [
        'id' => $position->id,
        'name' => 'New Name',
        'description' => 'Updated Description',
    ]);
});

it('can delete a position', function () {
    $user = User::factory()->create();
    $user->assignRole('Superadmin');

    $position = Position::factory()->create();

    $response = $this->actingAs($user)->delete("/position/{$position->id}/delete");

    $response->assertRedirect('/position');
    $this->assertDatabaseMissing('positions', [
        'id' => $position->id,
    ]);
});

it('can fetch positions datatable', function () {
    $user = User::factory()->create();
    $user->assignRole('Superadmin');

    Position::factory()->count(5)->create();

    $response = $this->actingAs($user)->get('/position/datatable');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data',
            'current_page',
            'last_page',
            'per_page',
            'total',
        ]);
});

it('can search positions in datatable', function () {
    $user = User::factory()->create();
    $user->assignRole('Superadmin');

    Position::factory()->create(['name' => 'Manager']);
    Position::factory()->create(['name' => 'Staff']);
    Position::factory()->create(['name' => 'Director']);

    $response = $this->actingAs($user)->get('/position/datatable?search=staff');

    $response->assertStatus(200);
    $data = $response->json('data');

    expect($data)->toHaveCount(1)
        ->and($data[0]['name'])->toBe('Staff');
});

it('can paginate positions in datatable', function () {
    $user = User::factory()->create();
    $user->assignRole('Superadmin');

    Position::factory()->count(25)->create();

    $response = $this->actingAs($user)->get('/position/datatable?limit=10');

    $response->assertStatus(200);
    $json = $response->json();

    expect($json['data'])->toHaveCount(10)
        ->and($json['total'])->toBe(25);
});

it('prevents unauthenticated access to positions', function () {
    $response = $this->get('/position');

    $response->assertRedirect('/login');
});
