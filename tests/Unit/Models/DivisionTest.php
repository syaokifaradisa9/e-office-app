<?php

use App\Models\Division;
use App\Models\User;

it('has correct fillable attributes', function () {
    $division = new Division;

    expect($division->getFillable())->toBe([
        'name',
        'description',
        'is_active',
    ]);
});

it('casts is_active to boolean', function () {
    $division = Division::factory()->create(['is_active' => 1]);

    expect($division->is_active)->toBeTrue()
        ->and($division->is_active)->toBeBool();
});

it('has many users relationship', function () {
    $division = Division::factory()->create();
    $user = User::factory()->create(['division_id' => $division->id]);

    expect($division->users)->toHaveCount(1)
        ->and($division->users->first()->id)->toBe($user->id);
});

it('can create division with factory', function () {
    $division = Division::factory()->create();

    expect($division)->toBeInstanceOf(Division::class)
        ->and($division->name)->not->toBeEmpty()
        ->and($division->is_active)->toBeTrue();
});

it('can create inactive division with factory state', function () {
    $division = Division::factory()->inactive()->create();

    expect($division->is_active)->toBeFalse();
});
