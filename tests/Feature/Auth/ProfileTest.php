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

it('can display edit profile page', function () {
    $response = $this->actingAs($this->user)->get('/profile');

    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Profile/Edit'));
});

it('can update profile information', function () {
    $response = $this->actingAs($this->user)->put('/profile/update', [
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
        'phone' => '08987654321',
    ]);

    $response->assertRedirect();
    $this->user->refresh();

    expect($this->user->name)->toBe('Updated Name');
    expect($this->user->email)->toBe('updated@example.com');
    expect($this->user->phone)->toBe('08987654321');
});

it('cannot update profile with invalid email', function () {
    $response = $this->actingAs($this->user)->put('/profile/update', [
        'name' => 'Updated Name',
        'email' => 'not-an-email',
    ]);

    $response->assertSessionHasErrors('email');
});

it('can display change password page', function () {
    $response = $this->actingAs($this->user)->get('/profile/password');

    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Profile/Password'));
});

it('can update password with valid current password', function () {
    $response = $this->actingAs($this->user)->put('/profile/password/update', [
        'current_password' => 'oldpassword',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertRedirect();
    $this->user->refresh();

    expect(Hash::check('newpassword123', $this->user->password))->toBeTrue();
});

it('cannot update password with incorrect current password', function () {
    $response = $this->actingAs($this->user)->put('/profile/password/update', [
        'current_password' => 'wrongpassword',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertSessionHasErrors('current_password');
    expect(Hash::check('oldpassword', $this->user->password))->toBeTrue();
});

it('requires password confirmation when updating password', function () {
    $response = $this->actingAs($this->user)->put('/profile/password/update', [
        'current_password' => 'oldpassword',
        'password' => 'newpassword123',
        'password_confirmation' => 'mismatch',
    ]);

    $response->assertSessionHasErrors('password');
});
