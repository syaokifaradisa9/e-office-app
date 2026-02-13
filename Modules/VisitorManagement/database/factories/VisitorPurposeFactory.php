<?php

namespace Modules\VisitorManagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\VisitorManagement\Models\VisitorPurpose;

class VisitorPurposeFactory extends Factory
{
    protected $model = VisitorPurpose::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
