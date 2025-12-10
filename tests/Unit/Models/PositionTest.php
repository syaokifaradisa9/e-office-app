<?php

use App\Models\Position;
use App\Models\User;

it('has correct fillable attributes', function () {
    $position = new Position;

    expect($position->getFillable())->toBe([
        'name',
        'description',
        'is_active',
    ]);
});

it('casts is_active to boolean', function () {
    $position = Position::factory()->create(['is_active' => 1]);

    expect($position->is_active)->toBeTrue()
        ->and($position->is_active)->toBeBool();
});

it('has many users relationship', function () {
    $position = Position::factory()->create();
    $user = User::factory()->create(['position_id' => $position->id]);

    expect($position->users)->toHaveCount(1)
        ->and($position->users->first()->id)->toBe($user->id);
});

it('can create position with factory', function () {
    $position = Position::factory()->create();

    expect($position)->toBeInstanceOf(Position::class)
        ->and($position->name)->not->toBeEmpty()
        ->and($position->is_active)->toBeTrue();
});

it('can create inactive position with factory state', function () {
    $position = Position::factory()->inactive()->create();

    expect($position->is_active)->toBeFalse();
});
