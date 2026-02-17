<?php

use App\Models\Division;
use App\Models\Position;
use App\Models\User;

/**
 * Memastikan atribut fillable pada model User sudah benar.
 */
it('has correct fillable attributes', function () {
    $user = new User;

    expect($user->getFillable())->toContain('name')
        ->and($user->getFillable())->toContain('email')
        ->and($user->getFillable())->toContain('password')
        ->and($user->getFillable())->toContain('division_id')
        ->and($user->getFillable())->toContain('position_id')
        ->and($user->getFillable())->toContain('is_active');
});

/**
 * Memastikan is_active di-cast ke boolean.
 */
it('casts is_active to boolean', function () {
    $user = User::factory()->create(['is_active' => 1]);

    expect($user->is_active)->toBeTrue()
        ->and($user->is_active)->toBeBool();
});

/**
 * Memastikan password di-hash otomatis saat disimpan.
 */
it('hashes password automatically', function () {
    $user = User::factory()->create(['password' => 'secret123']);

    expect($user->password)->not->toBe('secret123')
        ->and(strlen($user->password))->toBeGreaterThan(50);
});

it('hides password and remember_token in serialization', function () {
    $user = User::factory()->create();

    expect($user->toArray())->not->toHaveKey('password')
        ->and($user->toArray())->not->toHaveKey('remember_token');
});

/**
 * Test relasi belonsTo ke Division.
 */
it('belongs to division relationship', function () {
    $division = Division::factory()->create();
    $user = User::factory()->create(['division_id' => $division->id]);

    expect($user->division)->toBeInstanceOf(Division::class)
        ->and($user->division->id)->toBe($division->id);
});

it('belongs to position relationship', function () {
    $position = Position::factory()->create();
    $user = User::factory()->create(['position_id' => $position->id]);

    expect($user->position)->toBeInstanceOf(Position::class)
        ->and($user->position->id)->toBe($position->id);
});

it('can have division and position', function () {
    $user = User::factory()->withDivisionAndPosition()->create();

    expect($user->division)->not->toBeNull()
        ->and($user->position)->not->toBeNull();
});

/**
 * Test aksesor untuk mendapatkan inisial nama (John Doe -> JD).
 */
it('generates initials correctly', function () {
    $user = User::factory()->create(['name' => 'John Doe']);

    expect($user->initials)->toBe('JD');
});

/**
 * Inisial untuk nama tunggal.
 */
it('generates initials for single name', function () {
    $user = User::factory()->create(['name' => 'John']);

    expect($user->initials)->toBe('J');
});

/**
 * Batasi inisial maksimal 2 karakter.
 */
it('limits initials to two characters', function () {
    $user = User::factory()->create(['name' => 'John Doe Smith']);

    expect($user->initials)->toBe('JD');
});

it('can create inactive user with factory state', function () {
    $user = User::factory()->inactive()->create();

    expect($user->is_active)->toBeFalse();
});
