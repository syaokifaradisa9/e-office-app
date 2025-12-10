<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get divisions and positions
        $divisionIT = Division::where('name', 'Bagian Teknologi Informasi')->first();
        $divisionUmum = Division::where('name', 'Bagian Umum')->first();
        $divisionKeuangan = Division::where('name', 'Bagian Keuangan')->first();

        $positionKabag = Position::where('name', 'Kepala Bagian')->first();
        $positionStaff = Position::where('name', 'Staff')->first();
        $positionProgrammer = Position::where('name', 'Programmer')->first();

        // Create Superadmin user
        $superadmin = User::firstOrCreate(
            ['email' => 'superadmin@eoffice.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'phone' => '08123456789',
                'is_active' => true,
                'division_id' => $divisionIT?->id,
                'position_id' => $positionKabag?->id,
            ]
        );
        $superadmin->assignRole('Superadmin');

        // Create Admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@eoffice.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'phone' => '08234567890',
                'is_active' => true,
                'division_id' => $divisionUmum?->id,
                'position_id' => $positionStaff?->id,
            ]
        );
        $admin->assignRole('Admin');

        // Create regular User
        $user = User::firstOrCreate(
            ['email' => 'user@eoffice.com'],
            [
                'name' => 'Regular User',
                'password' => Hash::make('password'),
                'phone' => '08345678901',
                'is_active' => true,
                'division_id' => $divisionKeuangan?->id,
                'position_id' => $positionStaff?->id,
            ]
        );
        $user->assignRole('User');

        // Create additional sample users
        $sampleUsers = [
            [
                'name' => 'Budi Santoso',
                'email' => 'budi.santoso@eoffice.com',
                'division_id' => $divisionIT?->id,
                'position_id' => $positionProgrammer?->id,
                'role' => 'User',
            ],
            [
                'name' => 'Siti Rahma',
                'email' => 'siti.rahma@eoffice.com',
                'division_id' => $divisionKeuangan?->id,
                'position_id' => $positionStaff?->id,
                'role' => 'User',
            ],
            [
                'name' => 'Ahmad Wijaya',
                'email' => 'ahmad.wijaya@eoffice.com',
                'division_id' => $divisionUmum?->id,
                'position_id' => $positionStaff?->id,
                'role' => 'User',
            ],
        ];

        foreach ($sampleUsers as $userData) {
            $role = $userData['role'];
            unset($userData['role']);

            $newUser = User::firstOrCreate(
                ['email' => $userData['email']],
                array_merge($userData, [
                    'password' => Hash::make('password'),
                    'phone' => fake()->phoneNumber(),
                    'is_active' => true,
                ])
            );
            $newUser->assignRole($role);
        }
    }
}
