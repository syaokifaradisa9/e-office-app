<?php

namespace Database\Seeders;

use App\Models\Position;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $positions = [
            [
                'name' => 'Kepala Bagian',
                'description' => 'Memimpin dan mengkoordinasikan kegiatan bagian',
                'is_active' => true,
            ],
            [
                'name' => 'Kepala Sub Bagian',
                'description' => 'Memimpin dan mengkoordinasikan kegiatan sub bagian',
                'is_active' => true,
            ],
            [
                'name' => 'Staff',
                'description' => 'Melaksanakan tugas-tugas operasional',
                'is_active' => true,
            ],
            [
                'name' => 'Analis',
                'description' => 'Melakukan analisa dan evaluasi',
                'is_active' => true,
            ],
            [
                'name' => 'Programmer',
                'description' => 'Mengembangkan dan memelihara sistem informasi',
                'is_active' => true,
            ],
            [
                'name' => 'Administrator',
                'description' => 'Mengelola administrasi dan dokumentasi',
                'is_active' => true,
            ],
        ];

        foreach ($positions as $position) {
            Position::firstOrCreate(
                ['name' => $position['name']],
                $position
            );
        }
    }
}
