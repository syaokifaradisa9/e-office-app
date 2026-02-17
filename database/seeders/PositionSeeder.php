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
            ['name' => 'Direktur Utama'],
            ['name' => 'Kepala Divisi'],
            ['name' => 'Sekretaris'],
            ['name' => 'Bendahara'],
            ['name' => 'Staf Administrasi'],
            ['name' => 'Staf Teknis'],
            ['name' => 'Staf IT'],
            ['name' => 'Arsiparis'],
        ];

        foreach ($positions as $position) {
            Position::firstOrCreate($position);
        }
    }
}
