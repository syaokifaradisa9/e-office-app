<?php

namespace Modules\VisitorManagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\VisitorManagement\Models\VisitorFeedbackQuestion;

class VisitorFeedbackQuestionFactory extends Factory
{
    protected $model = VisitorFeedbackQuestion::class;

    public function definition(): array
    {
        return [
            'question' => $this->faker->sentence() . '?',
            'is_active' => true,
        ];
    }
}
