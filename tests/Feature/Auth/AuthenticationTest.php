<?php

use App\Models\User;

/**
 * Memastikan halaman login dapat dimuat (Inertia component 'Auth/Login').
 */
it('can display login page', function () {
    // 1. Request ke route login
    $response = $this->get('/auth/login');

    // 2. Validasi status 200 dan komponen Inertia yang digunakan
    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Auth/Login'));
});

/**
 * Test login berhasil dengan kredensial yang valid.
 */
it('can authenticate with valid credentials', function () {
    // 1. Persiapan user aktif
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);

    // 2. Kirim request verifikasi login
    $response = $this->post('/auth/verify', [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    // 3. Validasi redirect ke dashboard dan status terotentikasi
    $response->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);
});

/**
 * Test login gagal karena email salah.
 */
it('cannot authenticate with invalid email', function () {
    // 1. Persiapan user
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    // 2. Request dengan email berbeda
    $response = $this->post('/auth/verify', [
        'email' => 'wrong@example.com',
        'password' => 'password',
    ]);

    // 3. Validasi error email di session dan status tetap guest
    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

/**
 * Test login gagal karena password salah.
 */
it('cannot authenticate with invalid password', function () {
    // 1. Persiapan user
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    // 2. Request dengan password salah
    $response = $this->post('/auth/verify', [
        'email' => 'test@example.com',
        'password' => 'wrongpassword',
    ]);

    // 3. Validasi error dan status guest
    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

/**
 * Test login ditolak karena status user tidak aktif (is_active = false).
 */
it('cannot authenticate with inactive user', function () {
    // 1. Persiapan user tidak aktif
    User::factory()->create([
        'email' => 'inactive@example.com',
        'password' => bcrypt('password'),
        'is_active' => false,
    ]);

    // 2. Request login
    $response = $this->post('/auth/verify', [
        'email' => 'inactive@example.com',
        'password' => 'password',
    ]);

    // 3. Validasi kegagalan
    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

/**
 * Validasi input email kosong saat login.
 */
it('requires email for login', function () {
    $response = $this->post('/auth/verify', [
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
});

/**
 * Validasi input password kosong saat login.
 */
it('requires password for login', function () {
    $response = $this->post('/auth/verify', [
        'email' => 'test@example.com',
    ]);

    $response->assertSessionHasErrors('password');
});

/**
 * Test fitur logout bagi user yang sudah login.
 */
it('can logout authenticated user', function () {
    // 1. Login sebagai user
    $user = User::factory()->create();
    $this->actingAs($user);
    $this->assertAuthenticated();

    // 2. Request logout
    $response = $this->post('/auth/logout');

    // 3. Validasi redirect ke login dan status menjadi guest kembali
    $response->assertRedirect('/auth/login');
    $this->assertGuest();
});

/**
 * Proteksi route dashboard bagi tamu (guest).
 */
it('redirects guest to login when accessing dashboard', function () {
    $response = $this->get('/dashboard');
    $response->assertRedirect('/login');
});

/**
 * Memastikan user terotentikasi bisa masuk ke dashboard.
 */
it('allows authenticated user to access dashboard', function () {
    $user = User::factory()->create();
    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertStatus(200);
});

/**
 * Redirect path akar (root) ke halaman login.
 */
it('redirects root path to login', function () {
    $response = $this->get('/');
    $response->assertRedirect('/auth/login');
});
