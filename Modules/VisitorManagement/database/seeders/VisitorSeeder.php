<?php

namespace Modules\VisitorManagement\Database\Seeders;

use App\Models\Division;
use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\VisitorManagement\Models\Visitor;
use Modules\VisitorManagement\Models\VisitorPurpose;
use Modules\VisitorManagement\Enums\VisitorStatus;

class VisitorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $divisions = Division::all();
        $purposes = VisitorPurpose::all();
        $users = User::all();

        if ($divisions->isEmpty() || $purposes->isEmpty()) {
            return;
        }

        // 1. Seed Pending Visitors
        Visitor::factory()->count(10)->create([
            'status' => VisitorStatus::Pending,
            'division_id' => fn() => $divisions->random()->id,
            'purpose_id' => fn() => $purposes->random()->id,
        ]);

        // 2. Seed Approved Visitors
        Visitor::factory()->count(5)->create([
            'status' => VisitorStatus::Approved,
            'division_id' => fn() => $divisions->random()->id,
            'purpose_id' => fn() => $purposes->random()->id,
            'confirmed_by' => fn() => $users->random()->id,
            'confirmed_at' => now()->subMinutes(rand(5, 60)),
        ]);

        // 3. Seed Completed Visitors
        Visitor::factory()->count(15)->create([
            'status' => VisitorStatus::Completed,
            'division_id' => fn() => $divisions->random()->id,
            'purpose_id' => fn() => $purposes->random()->id,
            'check_in_at' => fn() => now()->subDays(rand(1, 30))
                ->setHour(rand(8, 11))
                ->setMinute(rand(0, 59)),
            'check_out_at' => fn($attributes) => (clone $attributes['check_in_at'])
                ->setHour(rand(14, 16))
                ->setMinute(rand(0, 59)),
            'confirmed_by' => fn() => $users->random()->id,
            'confirmed_at' => fn($attributes) => (clone $attributes['check_in_at'])->subMinutes(rand(10, 30)),
        ]);

        // 4. Seed Invitations
        Visitor::factory()->count(8)->create([
            'status' => VisitorStatus::Invited,
            'division_id' => fn() => $divisions->random()->id,
            'purpose_id' => fn() => $purposes->random()->id,
            'check_in_at' => null,
            'photo_url' => null,
        ]);

        // 5. Seed Rejected & Cancelled
        Visitor::factory()->count(3)->create([
            'status' => VisitorStatus::Rejected,
            'admin_note' => 'Personil yang bersangkutan sedang tidak ada di tempat.',
            'confirmed_by' => fn() => $users->random()->id,
            'confirmed_at' => now()->subDays(2),
        ]);
    }
}
