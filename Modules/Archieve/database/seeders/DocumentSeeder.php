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
                    'max_size' => 5 * 1024 * 1024 * 1024, // 5GB default for seeding
                    'used_size' => 0
                ]
            );

            $this->command->info("Seeding documents for division: {$division->name}");

            // Loop through months from Jan 2025 to Feb 2026
            $startDate = new \DateTime('2025-01-01');
            $endDate = new \DateTime('2026-02-28');
            $interval = new \DateInterval('P1M');
            $period = new \DatePeriod($startDate, $interval, $endDate->modify('+1 day'));

            foreach ($period as $date) {
                $monthName = $date->format('F Y');
                $numDocs = rand(5, 20);
                
                for ($i = 1; $i <= $numDocs; $i++) {
                    $classification = $classifications->random();
                    $title = "Arsip " . $classification->name . " " . $division->name . " " . $monthName . " " . $i;
                    
                    $sizeInMb = rand(1, 3);
                    $fileSize = $sizeInMb * 1024 * 1024;
                    
                    // Generate filename
                    $fileName = Str::slug($title) . "_" . Str::random(5) . ".pdf";
                    $filePath = "archieve/documents/" . $fileName;
                    
                    // Put dummy file
                    Storage::disk('public')->put($filePath, "Dummy content for " . $title);
                    
                    // Random day in that month
                    $day = rand(1, (int)$date->format('t'));
                    $documentDate = clone $date;
                    $documentDate->setDate((int)$date->format('Y'), (int)$date->format('m'), $day);
                    $timestamp = $documentDate->format('Y-m-d H:i:s');

                    $uploader = User::where('division_id', $division->id)->first() ?? $admin;

                    $document = Document::create([
                        'title' => $title,
                        'description' => "Deskripsi dummy untuk arsip " . $monthName . " pada divisi " . $division->name,
                        'classification_id' => $classification->id,
                        'file_path' => $filePath,
                        'file_name' => $fileName,
                        'file_type' => 'application/pdf',
                        'file_size' => $fileSize,
                        'uploaded_by' => $uploader->id,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ]);

                    // Link to division
                    $document->divisions()->attach($division->id, [
                        'allocated_size' => $fileSize,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ]);

                    // Update division storage cache
                    $storage->increment('used_size', $fileSize);

                    // Randomly link to categories
                    if ($categories->isNotEmpty()) {
                        $randomCategories = $categories->random(rand(1, min(2, $categories->count())));
                        $document->categories()->attach($randomCategories->pluck('id')->toArray());
                    }
                }
            }
        }
    }
}
