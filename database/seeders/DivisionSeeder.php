<?php

namespace Database\Seeders;

use App\Models\Division;
use Illuminate\Database\Seeder;

class DivisionSeeder extends Seeder
{
    public function run(): void
    {
        $divisions = [
            ['name' => 'Tata Usaha', 'description' => 'Divisi Tata Usaha dan Administrasi'],
            ['name' => 'Keuangan', 'description' => 'Divisi Keuangan dan Akuntansi'],
            ['name' => 'Kepegawaian', 'description' => 'Divisi Kepegawaian dan SDM'],
            ['name' => 'Pelayanan', 'description' => 'Divisi Pelayanan Publik'],
            ['name' => 'Teknik', 'description' => 'Divisi Teknik dan Operasional'],
            ['name' => 'IT', 'description' => 'Divisi Teknologi Informasi'],
        ];

        foreach ($divisions as $division) {
            Division::firstOrCreate(
                ['name' => $division['name']],
                ['description' => $division['description']]
            );
        }
    }
}
