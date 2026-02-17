<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Division;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $divisions = Division::all();
        
        $adminGudangRole = Role::where('name', 'Admin Gudang Utama')->first();
        $adminDivisiRole = Role::where('name', 'Admin Gudang Divisi')->first();
        
        $users = [
            // Tata Usaha
            [
                'name' => 'Ahmad Fauzi',
                'email' => 'ahmad.fauzi@example.com',
                'division' => 'Tata Usaha',
                'role' => 'Admin Gudang Divisi',
            ],
            [
                'name' => 'Siti Rahayu',
                'email' => 'siti.rahayu@example.com',
                'division' => 'Tata Usaha',
                'role' => 'Admin Gudang Divisi',
            ],
            // Keuangan
            [
                'name' => 'Dewi Lestari',
                'email' => 'dewi.lestari@example.com',
                'division' => 'Keuangan',
                'role' => 'Admin Gudang Divisi',
            ],
            [
                'name' => 'Rudi Hartono',
                'email' => 'rudi.hartono@example.com',
                'division' => 'Keuangan',
                'role' => 'Admin Gudang Divisi',
            ],
            // Teknik
            [
                'name' => 'Hendra Kusuma',
                'email' => 'hendra.kusuma@example.com',
                'division' => 'Teknik',
                'role' => 'Admin Gudang Divisi',
            ],
            [
                'name' => 'Eko Prasetyo',
                'email' => 'eko.prasetyo@example.com',
                'division' => 'Teknik',
                'role' => 'Admin Gudang Divisi',
            ],
            // Kepegawaian
            [
                'name' => 'Bambang Wijaya',
                'email' => 'bambang.wijaya@example.com',
                'division' => 'Kepegawaian',
                'role' => 'Admin Gudang Divisi',
            ],
            // Pelayanan
            [
                'name' => 'Andi Pratama',
                'email' => 'andi.pratama@example.com',
                'division' => 'Pelayanan',
                'role' => 'Admin Gudang Divisi',
            ],
            [
                'name' => 'Sinta Bella',
                'email' => 'sinta.bella@example.com',
                'division' => 'Pelayanan',
                'role' => 'Admin Gudang Divisi',
            ],
            // IT
            [
                'name' => 'Irfan Hakim',
                'email' => 'irfan.hakim@example.com',
                'division' => 'IT',
                'role' => 'Admin Gudang Divisi',
            ],
            [
                'name' => 'Budi Setiawan',
                'email' => 'admin.it@example.com', // Reuse existing email to avoid duplicates if possible, or just update it
                'division' => 'IT',
                'role' => 'Admin Gudang Divisi',
            ],
            // Gudang Utama Staff
            [
                'name' => 'Ryan Hidayat',
                'email' => 'ryan.hidayat@example.com',
                'division' => null,
                'role' => 'Admin Gudang Utama',
            ],
            [
                'name' => 'Fajar Nugroho',
                'email' => 'fajar.nugroho@example.com',
                'division' => null,
                'role' => 'Admin Gudang Utama',
            ],
        ];

        foreach ($users as $userData) {
            $division = $userData['division'] ? $divisions->firstWhere('name', $userData['division']) : null;
            
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                    'division_id' => $division?->id,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            if ($userData['role']) {
                $role = Role::where('name', $userData['role'])->first();
                if ($role) {
                    $user->assignRole($role);
                }
            }
        }
    }
}
