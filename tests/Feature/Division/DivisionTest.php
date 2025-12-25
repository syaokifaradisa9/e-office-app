<?php

use App\Models\Division;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Modules\Inventory\Database\Seeders\InventoryPermissionSeeder;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(InventoryPermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

it('can display division index page', function () {
    $user = User::factory()->create();
    $user->assignRole('Superadmin');

    $response = $this->actingAs($user)->get('/division');

    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Division/Index'));
});

it('can display division create page', function () {
    $user = User::factory()->create();
    $user->assignRole('Superadmin');

    $response = $this->actingAs($user)->get('/division/create');

    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Division/Create'));
});

it('can create a new division', function () {
    $user = User::factory()->create();
    $user->assignRole('Superadmin');

    $response = $this->actingAs($user)->post('/division/store', [
        'name' => 'Test Division',
        'description' => 'Test Description',
        'is_active' => true,
    ]);

    $response->assertRedirect('/division');
    $this->assertDatabaseHas('divisions', [
        'name' => 'Test Division',
        'description' => 'Test Description',
    ]);
});

it('requires name when creating division', function () {
    $user = User::factory()->create();
    $user->assignRole('Superadmin');

    $response = $this->actingAs($user)->post('/division/store', [
        'description' => 'Test Description',
    ]);

    $response->assertSessionHasErrors('name');
});

it('can display division edit page', function () {
    $user = User::factory()->create();
    $user->assignRole('Superadmin');

    $division = Division::factory()->create();

    $response = $this->actingAs($user)->get("/division/{$division->id}/edit");

    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Division/Create')
            ->has('division'));
});

it('can update an existing division', function () {
    $user = User::factory()->create();
    $user->assignRole('Superadmin');

    $division = Division::factory()->create([
        'name' => 'Old Name',
    ]);

    $response = $this->actingAs($user)->put("/division/{$division->id}/update", [
        'name' => 'New Name',
        'description' => 'Updated Description',
        'is_active' => true,
    ]);

    $response->assertRedirect('/division');
    $this->assertDatabaseHas('divisions', [
        'id' => $division->id,
        'name' => 'New Name',
        'description' => 'Updated Description',
    ]);
});

it('can delete a division', function () {
    $user = User::factory()->create();
    $user->assignRole('Superadmin');

    $division = Division::factory()->create();

    $response = $this->actingAs($user)->delete("/division/{$division->id}/delete");

    $response->assertRedirect('/division');
    $this->assertDatabaseMissing('divisions', [
        'id' => $division->id,
    ]);
});

it('can fetch divisions datatable', function () {
    $user = User::factory()->create();
    $user->assignRole('Superadmin');

    Division::factory()->count(5)->create();

    $response = $this->actingAs($user)->get('/division/datatable');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data',
            'current_page',
            'last_page',
            'per_page',
            'total',
        ]);
});

it('can search divisions in datatable', function () {
    $user = User::factory()->create();
    $user->assignRole('Superadmin');

    Division::factory()->create(['name' => 'Bagian IT']);
    Division::factory()->create(['name' => 'Bagian Keuangan']);
    Division::factory()->create(['name' => 'Bagian Umum']);

    $response = $this->actingAs($user)->get('/division/datatable?search=keuangan');

    $response->assertStatus(200);
    $data = $response->json('data');

    expect($data)->toHaveCount(1)
        ->and($data[0]['name'])->toBe('Bagian Keuangan');
});

it('can paginate divisions in datatable', function () {
    $user = User::factory()->create();
    $user->assignRole('Superadmin');

    Division::factory()->count(25)->create();

    $response = $this->actingAs($user)->get('/division/datatable?limit=10');

    $response->assertStatus(200);
    $json = $response->json();

    expect($json['data'])->toHaveCount(10)
        ->and($json['total'])->toBe(25);
});

it('prevents unauthenticated access to divisions', function () {
    $response = $this->get('/division');

    $response->assertRedirect('/login');
});
