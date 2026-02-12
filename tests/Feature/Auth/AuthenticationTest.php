<?php

use App\Models\User;

it('can display login page', function () {
    $response = $this->get('/auth/login');

    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Auth/Login'));
});

it('can authenticate with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);

    $response = $this->post('/auth/verify', [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);
});

it('cannot authenticate with invalid email', function () {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    $response = $this->post('/auth/verify', [
        'email' => 'wrong@example.com',
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('cannot authenticate with invalid password', function () {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    $response = $this->post('/auth/verify', [
        'email' => 'test@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('cannot authenticate with inactive user', function () {
    User::factory()->create([
        'email' => 'inactive@example.com',
        'password' => bcrypt('password'),
        'is_active' => false,
    ]);

    $response = $this->post('/auth/verify', [
        'email' => 'inactive@example.com',
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('requires email for login', function () {
    $response = $this->post('/auth/verify', [
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
});

it('requires password for login', function () {
    $response = $this->post('/auth/verify', [
        'email' => 'test@example.com',
    ]);

    $response->assertSessionHasErrors('password');
});

it('can logout authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user);
    $this->assertAuthenticated();

    $response = $this->post('/auth/logout');

    $response->assertRedirect('/auth/login');
    $this->assertGuest();
});

it('redirects guest to login when accessing dashboard', function () {
    $response = $this->get('/dashboard');

    $response->assertRedirect('/login');
});

it('allows authenticated user to access dashboard', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
});

it('redirects root path to login', function () {
    $response = $this->get('/');

    $response->assertRedirect('/auth/login');
});
