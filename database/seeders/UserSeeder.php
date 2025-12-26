<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $divisions = Division::all();

        if ($divisions->isEmpty()) {
            $this->command->warn('Tidak ada divisi. Jalankan DivisionSeeder terlebih dahulu.');
            return;
        }

        $users = [
            // Tata Usaha
            ['name' => 'Ahmad Fauzi', 'email' => 'ahmad.fauzi@example.com', 'division' => 'Tata Usaha'],
            ['name' => 'Siti Rahayu', 'email' => 'siti.rahayu@example.com', 'division' => 'Tata Usaha'],
            ['name' => 'Budi Santoso', 'email' => 'budi.santoso@example.com', 'division' => 'Tata Usaha'],
            
            // Keuangan
            ['name' => 'Dewi Lestari', 'email' => 'dewi.lestari@example.com', 'division' => 'Keuangan'],
            ['name' => 'Rudi Hartono', 'email' => 'rudi.hartono@example.com', 'division' => 'Keuangan'],
            ['name' => 'Rina Wati', 'email' => 'rina.wati@example.com', 'division' => 'Keuangan'],
            
            // Kepegawaian
            ['name' => 'Agus Prabowo', 'email' => 'agus.prabowo@example.com', 'division' => 'Kepegawaian'],
            ['name' => 'Nur Hidayah', 'email' => 'nur.hidayah@example.com', 'division' => 'Kepegawaian'],
            ['name' => 'Joko Widodo', 'email' => 'joko.widodo@example.com', 'division' => 'Kepegawaian'],
            
            // Pelayanan
            ['name' => 'Mega Sari', 'email' => 'mega.sari@example.com', 'division' => 'Pelayanan'],
            ['name' => 'Andi Pratama', 'email' => 'andi.pratama@example.com', 'division' => 'Pelayanan'],
            ['name' => 'Lisa Permata', 'email' => 'lisa.permata@example.com', 'division' => 'Pelayanan'],
            
            // Teknik
            ['name' => 'Hendra Kusuma', 'email' => 'hendra.kusuma@example.com', 'division' => 'Teknik'],
            ['name' => 'Eko Prasetyo', 'email' => 'eko.prasetyo@example.com', 'division' => 'Teknik'],
            ['name' => 'Dian Saputra', 'email' => 'dian.saputra@example.com', 'division' => 'Teknik'],
            
            // IT
            ['name' => 'Ryan Hidayat', 'email' => 'ryan.hidayat@example.com', 'division' => 'IT'],
            ['name' => 'Fajar Nugroho', 'email' => 'fajar.nugroho@example.com', 'division' => 'IT'],
            ['name' => 'Indra Wijaya', 'email' => 'indra.wijaya@example.com', 'division' => 'IT'],
        ];

        foreach ($users as $userData) {
            $division = $divisions->firstWhere('name', $userData['division']);

            if (!$division) {
                continue;
            }

            User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                    'division_id' => $division->id,
                    'is_active' => true,
                ]
            );
        }
    }
}
