<?php

namespace Modules\Archieve\Database\Seeders;

use App\Models\Division;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Archieve\Models\Document;
use Modules\Archieve\Models\DocumentClassification;
use Modules\Archieve\Models\DivisionStorage;
use Modules\Archieve\Models\Category;

class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        $divisions = Division::all();
        if ($divisions->isEmpty()) {
            $this->command->info('No divisions found. Please seed divisions first.');
            return;
        }

        $classifications = DocumentClassification::whereNotNull('parent_id')->get();
        if ($classifications->isEmpty()) {
            $classifications = DocumentClassification::all();
        }

        $categories = Category::all();
        $admin = User::first();

        foreach ($divisions as $division) {
            // Ensure division storage exists
            $storage = DivisionStorage::firstOrCreate(
                ['division_id' => $division->id],
                [
                    'max_size' => 1024 * 1024 * 1024, // 1GB default
                    'used_size' => 0
                ]
            );

            $this->command->info("Seeding documents for division: {$division->name}");

            for ($i = 1; $i <= 10; $i++) {
                $classification = $classifications->random();
                $title = "Arsip " . $classification->name . " " . $division->name . " " . $i;
                
                $sizeInMb = rand(1, 3);
                $fileSize = $sizeInMb * 1024 * 1024;
                
                // Generate filename based on title
                $fileName = Str::slug($title) . "_" . Str::random(5) . ".pdf";
                $filePath = "archieve/documents/" . $fileName;
                
                // Construct dummy content of specific size
                $content = Str::random(1024); // 1KB
                $fullContent = str_repeat($content, $sizeInMb * 1024);
                
                Storage::disk('public')->put($filePath, $fullContent);
                
                // Find a user in this division if possible, otherwise use admin
                $uploader = User::where('division_id', $division->id)->first() ?? $admin;

                $document = Document::create([
                    'title' => $title,
                    'description' => "Deskripsi dummy untuk arsip dokumen " . $i . " pada divisi " . $division->name,
                    'classification_id' => $classification->id,
                    'file_path' => $filePath,
                    'file_name' => $fileName,
                    'file_type' => 'application/pdf',
                    'file_size' => $fileSize,
                    'uploaded_by' => $uploader->id,
                ]);

                // Link to division
                $document->divisions()->attach($division->id, [
                    'allocated_size' => $fileSize,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Update division storage
                $storage->increment('used_size', $fileSize);

                // Randomly link to 1-2 categories
                if ($categories->isNotEmpty()) {
                    $randomCategories = $categories->random(rand(1, min(2, $categories->count())));
                    $document->categories()->attach($randomCategories->pluck('id')->toArray());
                }
            }
        }
    }
}
