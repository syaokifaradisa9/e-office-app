<?php

use App\Models\User;
use Database\Seeders\InventoryModuleSeeder;
use Illuminate\Support\Facades\Hash;
use Modules\Inventory\Database\Seeders\InventoryPermissionSeeder;

beforeEach(function () {
    $this->seed(InventoryModuleSeeder::class);
});

/*
|--------------------------------------------------------------------------
| Edit Profile Page Tests
|--------------------------------------------------------------------------
*/

/**
 * Memastikan halaman edit profil dapat diakses.
 */
it('can display edit profile page', function () {
    // 1. Create user dan assign role
    $user = User::factory()->create();
    $user->assignRole('User');

    // 2. Akses profile
    $response = $this->actingAs($user)->get('/profile');

    // 3. Validasi status OK
    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Profile/Edit'));
});

it('prevents unauthenticated access to profile page', function () {
    $response = $this->get('/profile');

    $response->assertRedirect('/login');
});

/*
|--------------------------------------------------------------------------
| Update Profile Tests
|--------------------------------------------------------------------------
*/

/**
 * Test update profil dengan data valid.
 */
it('can update profile with valid data', function () {
    // 1. Persiapan data user lama
    $user = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'old@example.com',
        'phone' => '08123456789',
        'address' => 'Old Address',
    ]);
    $user->assignRole('User');

    // 2. Kirim update data baru
    $response = $this->actingAs($user)->put('/profile/update', [
        'name' => 'New Name',
        'email' => 'new@example.com',
        'phone' => '08987654321',
        'address' => 'New Address',
    ]);

    // 3. Validasi redirect dan pesan sukses
    $response->assertRedirect();
    $response->assertSessionHas('success');

    // 4. Pastikan data di DB berubah
    $user->refresh();
    expect($user->name)->toBe('New Name')
        ->and($user->email)->toBe('new@example.com')
        ->and($user->phone)->toBe('08987654321')
        ->and($user->address)->toBe('New Address');
});

it('requires name when updating profile', function () {
    $user = User::factory()->create();
    $user->assignRole('User');

    $response = $this->actingAs($user)->put('/profile/update', [
        'name' => '',
        'email' => $user->email,
    ]);

    $response->assertSessionHasErrors('name');
});

it('requires valid email when updating profile', function () {
    $user = User::factory()->create();
    $user->assignRole('User');

    $response = $this->actingAs($user)->put('/profile/update', [
        'name' => 'Test Name',
        'email' => 'invalid-email',
    ]);

    $response->assertSessionHasErrors('email');
});

it('requires unique email when updating profile', function () {
    $existingUser = User::factory()->create(['email' => 'existing@example.com']);

    $user = User::factory()->create();
    $user->assignRole('User');

    $response = $this->actingAs($user)->put('/profile/update', [
        'name' => 'Test Name',
        'email' => 'existing@example.com', // Trying to use another user's email
    ]);

    $response->assertSessionHasErrors('email');
});

it('allows user to keep their own email when updating profile', function () {
    $user = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'same@example.com',
    ]);
    $user->assignRole('User');

    $response = $this->actingAs($user)->put('/profile/update', [
        'name' => 'New Name',
        'email' => 'same@example.com', // Same email as before
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $user->refresh();
    expect($user->name)->toBe('New Name')
        ->and($user->email)->toBe('same@example.com');
});

it('validates phone max length when updating profile', function () {
    $user = User::factory()->create();
    $user->assignRole('User');

    $response = $this->actingAs($user)->put('/profile/update', [
        'name' => 'Test Name',
        'email' => $user->email,
        'phone' => str_repeat('0', 21), // Exceeds max 20 characters
    ]);

    $response->assertSessionHasErrors('phone');
});

it('validates address max length when updating profile', function () {
    $user = User::factory()->create();
    $user->assignRole('User');

    $response = $this->actingAs($user)->put('/profile/update', [
        'name' => 'Test Name',
        'email' => $user->email,
        'address' => str_repeat('A', 501), // Exceeds max 500 characters
    ]);

    $response->assertSessionHasErrors('address');
});

/*
|--------------------------------------------------------------------------
| Edit Password Page Tests
|--------------------------------------------------------------------------
*/

it('can display edit password page', function () {
    $user = User::factory()->create();
    $user->assignRole('User');

    $response = $this->actingAs($user)->get('/profile/password');

    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Profile/Password'));
});

it('prevents unauthenticated access to password page', function () {
    $response = $this->get('/profile/password');

    $response->assertRedirect('/login');
});

/*
|--------------------------------------------------------------------------
| Update Password Tests
|--------------------------------------------------------------------------
*/

/**
 * Test update password profil.
 */
it('can update password with valid data', function () {
    // 1. Persiapan user dengan password lama
    $user = User::factory()->create([
        'password' => Hash::make('currentpassword'),
    ]);
    $user->assignRole('User');

    // 2. Request ganti ke password baru
    $response = $this->actingAs($user)->put('/profile/password/update', [
        'current_password' => 'currentpassword',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    // 3. Validasi redirect dan kesesuaian password baru di DB
    $response->assertRedirect();
    $response->assertSessionHas('success');

    $user->refresh();
    expect(Hash::check('newpassword123', $user->password))->toBeTrue();
});

it('requires current password when updating password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('currentpassword'),
    ]);
    $user->assignRole('User');

    $response = $this->actingAs($user)->put('/profile/password/update', [
        'current_password' => '',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertSessionHasErrors('current_password');
});

it('validates current password is correct when updating password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('currentpassword'),
    ]);
    $user->assignRole('User');

    $response = $this->actingAs($user)->put('/profile/password/update', [
        'current_password' => 'wrongpassword',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertSessionHasErrors('current_password');
});

it('requires password confirmation when updating password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('currentpassword'),
    ]);
    $user->assignRole('User');

    $response = $this->actingAs($user)->put('/profile/password/update', [
        'current_password' => 'currentpassword',
        'password' => 'newpassword123',
        'password_confirmation' => 'differentpassword',
    ]);

    $response->assertSessionHasErrors('password');
});

it('requires new password when updating password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('currentpassword'),
    ]);
    $user->assignRole('User');

    $response = $this->actingAs($user)->put('/profile/password/update', [
        'current_password' => 'currentpassword',
        'password' => '',
        'password_confirmation' => '',
    ]);

    $response->assertSessionHasErrors('password');
});

it('prevents unauthenticated access to update password', function () {
    $response = $this->put('/profile/password/update', [
        'current_password' => 'currentpassword',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertRedirect('/login');
});
