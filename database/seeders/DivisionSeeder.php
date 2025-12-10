<?php

namespace Database\Seeders;

use App\Models\Division;
use Illuminate\Database\Seeder;

class DivisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $divisions = [
            [
                'name' => 'Bagian Umum',
                'description' => 'Mengelola administrasi umum dan kepegawaian',
                'is_active' => true,
            ],
            [
                'name' => 'Bagian Keuangan',
                'description' => 'Mengelola keuangan dan anggaran',
                'is_active' => true,
            ],
            [
                'name' => 'Bagian Teknologi Informasi',
                'description' => 'Mengelola infrastruktur dan sistem informasi',
                'is_active' => true,
            ],
            [
                'name' => 'Bagian Perencanaan',
                'description' => 'Mengelola perencanaan dan evaluasi program',
                'is_active' => true,
            ],
            [
                'name' => 'Bagian Hukum',
                'description' => 'Mengelola aspek hukum dan peraturan',
                'is_active' => true,
            ],
        ];

        foreach ($divisions as $division) {
            Division::firstOrCreate(
                ['name' => $division['name']],
                $division
            );
        }
    }
}
