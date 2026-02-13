<?php

namespace Modules\VisitorManagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\VisitorManagement\Models\Visitor;
use App\Models\Division;
use Modules\VisitorManagement\Models\VisitorPurpose;
use Modules\VisitorManagement\Enums\VisitorStatus;

class VisitorFactory extends Factory
{
    protected $model = Visitor::class;

    public function definition(): array
    {
        return [
            'visitor_name' => $this->faker->name(),
            'phone_number' => $this->faker->phoneNumber(),
            'organization' => $this->faker->company(),
            'photo_url' => null,
            'division_id' => Division::factory(),
            'purpose_id' => VisitorPurpose::factory(),
            'purpose_detail' => $this->faker->sentence(),
            'visitor_count' => rand(1, 5),
            'check_in_at' => now(),
            'status' => VisitorStatus::Pending,
        ];
    }
}
