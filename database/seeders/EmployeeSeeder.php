<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Division;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $divisions = Division::all();
        $pegawaiRole = Role::where('name', 'Pegawai')->first();

        foreach ($divisions as $division) {
            for ($i = 1; $i <= 5; $i++) {
                $divisionNameLower = strtolower(str_replace(' ', '', $division->name));
                $email = "pegawai.{$divisionNameLower}.{$i}@gmail.com";
                
                $user = User::updateOrCreate(
                    ['email' => $email],
                    [
                        'name' => "Pegawai {$division->name} {$i}",
                        'password' => Hash::make('password'),
                        'email_verified_at' => now(),
                        'is_active' => true,
                        'division_id' => $division->id,
                    ]
                );

                if ($pegawaiRole) {
                    $user->assignRole($pegawaiRole);
                }
            }
        }
    }
}
