<?php

namespace Modules\Archieve\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Archieve\Models\Document;
use Modules\Archieve\Models\DocumentClassification;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'classification_id' => DocumentClassification::factory(),
            'file_path' => 'archieve/documents/' . $this->faker->uuid() . '.pdf',
            'file_name' => $this->faker->word() . '.pdf',
            'file_type' => 'application/pdf',
            'file_size' => $this->faker->numberBetween(1024, 10485760), // 1KB to 10MB
            'uploaded_by' => User::factory(),
        ];
    }
}
