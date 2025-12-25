<?php

use App\Models\Division;
use App\Models\Position;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Modules\Inventory\Database\Seeders\InventoryPermissionSeeder;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(InventoryPermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

it('can display user index page', function () {
    $user = User::factory()->create();
    $user->assignRole('Superadmin');

    $response = $this->actingAs($user)->get('/user');

    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('User/Index'));
});

it('can display user create page', function () {
    $user = User::factory()->create();
    $user->assignRole('Superadmin');

    $response = $this->actingAs($user)->get('/user/create');

    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('User/Create')
            ->has('divisions')
            ->has('positions')
            ->has('roles'));
});

it('can create a new user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Superadmin');

    $division = Division::factory()->create();
    $position = Position::factory()->create();

    $response = $this->actingAs($admin)->post('/user/store', [
        'name' => 'Test User',
        'email' => 'testuser@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'division_id' => $division->id,
        'position_id' => $position->id,
        'role' => 'User',
        'is_active' => true,
    ]);

    $response->assertRedirect('/user');
    $this->assertDatabaseHas('users', [
        'name' => 'Test User',
        'email' => 'testuser@example.com',
        'division_id' => $division->id,
        'position_id' => $position->id,
    ]);
});

it('requires name when creating user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Superadmin');

    $response = $this->actingAs($admin)->post('/user/store', [
        'email' => 'testuser@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors('name');
});

it('requires valid email when creating user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Superadmin');

    $response = $this->actingAs($admin)->post('/user/store', [
        'name' => 'Test User',
        'email' => 'invalid-email',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors('email');
});

it('requires unique email when creating user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Superadmin');

    User::factory()->create(['email' => 'existing@example.com']);

    $response = $this->actingAs($admin)->post('/user/store', [
        'name' => 'Test User',
        'email' => 'existing@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors('email');
});

it('can display user edit page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Superadmin');

    $user = User::factory()->withDivisionAndPosition()->create();

    $response = $this->actingAs($admin)->get("/user/{$user->id}/edit");

    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('User/Create')
            ->has('user')
            ->has('divisions')
            ->has('positions')
            ->has('roles'));
});

it('can update an existing user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Superadmin');

    $user = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'old@example.com',
    ]);
    $user->assignRole('User');

    $division = Division::factory()->create();
    $position = Position::factory()->create();

    $response = $this->actingAs($admin)->put("/user/{$user->id}/update", [
        'name' => 'New Name',
        'email' => 'new@example.com',
        'division_id' => $division->id,
        'position_id' => $position->id,
        'role' => 'Admin',
        'is_active' => true,
    ]);

    $response->assertRedirect('/user');
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'New Name',
        'email' => 'new@example.com',
    ]);
});

it('can update user without changing password', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Superadmin');

    $user = User::factory()->create([
        'password' => bcrypt('oldpassword'),
    ]);
    $user->assignRole('User');

    $originalPasswordHash = $user->password;

    $response = $this->actingAs($admin)->put("/user/{$user->id}/update", [
        'name' => 'Updated Name',
        'email' => $user->email,
        'role' => 'User',
        'is_active' => true,
    ]);

    $response->assertRedirect('/user');

    $user->refresh();
    expect($user->password)->toBe($originalPasswordHash);
});

it('can delete a user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Superadmin');

    $user = User::factory()->create();

    $response = $this->actingAs($admin)->delete("/user/{$user->id}/delete");

    $response->assertRedirect('/user');
    $this->assertDatabaseMissing('users', [
        'id' => $user->id,
    ]);
});

it('can fetch users datatable', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Superadmin');

    User::factory()->count(5)->create();

    $response = $this->actingAs($admin)->get('/user/datatable');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data',
            'current_page',
            'last_page',
            'per_page',
            'total',
        ]);
});

it('can search users in datatable', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Superadmin');

    User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
    User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);
    User::factory()->create(['name' => 'Bob Wilson', 'email' => 'bob@example.com']);

    $response = $this->actingAs($admin)->get('/user/datatable?search=john');

    $response->assertStatus(200);
    $data = $response->json('data');

    expect($data)->toHaveCount(1)
        ->and($data[0]['name'])->toBe('John Doe');
});

it('can search users by email in datatable', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Superadmin');

    User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
    User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);

    $response = $this->actingAs($admin)->get('/user/datatable?search=jane@example');

    $response->assertStatus(200);
    $data = $response->json('data');

    expect($data)->toHaveCount(1)
        ->and($data[0]['email'])->toBe('jane@example.com');
});

it('can paginate users in datatable', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Superadmin');

    User::factory()->count(25)->create();

    $response = $this->actingAs($admin)->get('/user/datatable?limit=10');

    $response->assertStatus(200);
    $json = $response->json();

    expect($json['data'])->toHaveCount(10)
        ->and($json['total'])->toBe(26); // 25 + 1 admin
});

it('includes user relationships in datatable', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Superadmin');

    $division = Division::factory()->create();
    $position = Position::factory()->create();

    $user = User::factory()->create([
        'division_id' => $division->id,
        'position_id' => $position->id,
    ]);
    $user->assignRole('User');

    $response = $this->actingAs($admin)->get('/user/datatable');

    $response->assertStatus(200);
    $data = $response->json('data');

    $foundUser = collect($data)->firstWhere('id', $user->id);

    expect($foundUser)->not->toBeNull()
        ->and($foundUser['division']['id'])->toBe($division->id)
        ->and($foundUser['position']['id'])->toBe($position->id);
});

it('prevents unauthenticated access to users', function () {
    $response = $this->get('/user');

    $response->assertRedirect('/login');
});
