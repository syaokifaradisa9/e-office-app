<?php

namespace Modules\VisitorManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\VisitorManagement\Models\VisitorPurpose;
use Modules\VisitorManagement\Models\VisitorFeedbackQuestion;
use Modules\VisitorManagement\Enums\VisitorUserPermission;
use Spatie\Permission\Models\Permission;

class VisitorManagementDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed Permissions
        $permissions = VisitorUserPermission::values();
        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        // 2. Seed Purpose Categories
        $purposes = [
            ['name' => 'Kunjungan Dinas', 'description' => 'Tamu dari instansi pemerintah/swasta untuk urusan kedinasan.'],
            ['name' => 'Pengantaran Barang', 'description' => 'Kurir atau ekspedisi yang mengantar paket/barang.'],
            ['name' => 'Konsultasi / Audiensi', 'description' => 'Tamu yang ingin berkonsultasi dengan pimpinan atau staf.'],
            ['name' => 'Pelayanan Publik', 'description' => 'Masyarakat yang ingin mendapatkan pelayanan langsung.'],
            ['name' => 'Pribadi', 'description' => 'Keperluan pribadi dengan salah satu pegawai.'],
        ];

        foreach ($purposes as $purpose) {
            VisitorPurpose::firstOrCreate(['name' => $purpose['name']], $purpose);
        }

        // 3. Seed Feedback Questions
        $questions = [
            'Bagaimana keramahan petugas resepsionis?',
            'Bagaimana kecepatan proses konfirmasi kunjungan?',
            'Bagaimana kenyamanan ruang tunggu?',
            'Apakah informasi yang diberikan petugas sudah jelas?',
            'Secara keseluruhan, bagaimana pengalaman kunjungan Anda?',
        ];

        foreach ($questions as $question) {
            VisitorFeedbackQuestion::firstOrCreate(['question' => $question]);
        }
    }
}
