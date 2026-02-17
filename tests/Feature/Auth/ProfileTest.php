<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->user = User::factory()->create([
        'name' => 'Original Name',
        'email' => 'original@example.com',
        'phone' => '08123456789',
        'password' => Hash::make('oldpassword'),
        'is_active' => true,
    ]);
});

/**
 * Memastikan halaman edit profil dapat dimuat.
 */
it('can display edit profile page', function () {
    // 1. Akses route profil
    $response = $this->actingAs($this->user)->get('/profile');

    // 2. Validasi status dan komponen Inertia
    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Profile/Edit'));
});

/**
 * Test update informasi profil (nama, email, telepon).
 */
it('can update profile information', function () {
    // 1. Kirim request update profil
    $response = $this->actingAs($this->user)->put('/profile/update', [
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
        'phone' => '08987654321',
    ]);

    // 2. Validasi redirect dan perubahan data di database
    $response->assertRedirect();
    $this->user->refresh();

    expect($this->user->name)->toBe('Updated Name');
    expect($this->user->email)->toBe('updated@example.com');
    expect($this->user->phone)->toBe('08987654321');
});

/**
 * Validasi email saat update profil.
 */
it('cannot update profile with invalid email', function () {
    $response = $this->actingAs($this->user)->put('/profile/update', [
        'name' => 'Updated Name',
        'email' => 'not-an-email',
    ]);

    $response->assertSessionHasErrors('email');
});

/**
 * Memastikan halaman ganti password dapat dimuat.
 */
it('can display change password page', function () {
    $response = $this->actingAs($this->user)->get('/profile/password');

    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Profile/Password'));
});

/**
 * Test update password dengan current password yang benar.
 */
it('can update password with valid current password', function () {
    // 1. Kirim request update password
    $response = $this->actingAs($this->user)->put('/profile/password/update', [
        'current_password' => 'oldpassword',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    // 2. Validasi redirect dan password baru ter-hash dengan benar
    $response->assertRedirect();
    $this->user->refresh();

    expect(Hash::check('newpassword123', $this->user->password))->toBeTrue();
});

/**
 * Test update password gagal karena current password salah.
 */
it('cannot update password with incorrect current password', function () {
    // 1. Kirim request dengan current password salah
    $response = $this->actingAs($this->user)->put('/profile/password/update', [
        'current_password' => 'wrongpassword',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    // 2. Validasi error dan password lama tidak berubah
    $response->assertSessionHasErrors('current_password');
    expect(Hash::check('oldpassword', $this->user->password))->toBeTrue();
});

/**
 * Validasi konfirmasi password baru (mismatch) saat update.
 */
it('requires password confirmation when updating password', function () {
    $response = $this->actingAs($this->user)->put('/profile/password/update', [
        'current_password' => 'oldpassword',
        'password' => 'newpassword123',
        'password_confirmation' => 'mismatch',
    ]);

    $response->assertSessionHasErrors('password');
});
