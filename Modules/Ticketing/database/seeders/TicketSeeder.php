<?php

namespace Modules\Ticketing\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Ticketing\Models\Ticket;
use Modules\Ticketing\Models\AssetItem;
use Modules\Ticketing\Models\AssetItemRefinement;
use Modules\Ticketing\Enums\TicketStatus;
use Modules\Ticketing\Enums\TicketPriority;
use Carbon\Carbon;

class TicketSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $assets = AssetItem::all();

        if ($users->isEmpty() || $assets->isEmpty()) {
            return;
        }

        $ticketCommonSubjects = [
            'Mouse Tidak Berfungsi',
            'Keyboard Rusak',
            'Laptop Sering Hang',
            'Printer Macet',
            'AC Tidak Dingin',
            'Internet Lambat di Ruangan',
            'Aplikasi Error saat Login',
            'Layar Monitor Bergaris',
            'Suara Berisik di Unit PC',
            'Kabel Charger Mengelupas'
        ];

        for ($i = 0;$i < 20;$i++) {
            $user = $users->random();
            $asset = $assets->random();
            $status = collect(TicketStatus::cases())->random();
            $priority = collect(TicketPriority::cases())->random();

            $ticket = Ticket::create([
                'user_id' => $user->id,
                'asset_item_id' => $asset->id,
                'status' => $status,
                'priority' => $priority,
                'subject' => $ticketCommonSubjects[array_rand($ticketCommonSubjects)],
                'description' => 'Contoh deskripsi kendala untuk ' . $asset->merk . ' ' . $asset->model,
                'created_at' => Carbon::now()->subDays(rand(1, 30)),
                'rating' => in_array($status, [TicketStatus::FINISH, TicketStatus::CLOSED]) ? rand(3, 5) : null,
                'feedback_description' => in_array($status, [TicketStatus::FINISH, TicketStatus::CLOSED]) ? 'Pelayanan cepat dan ramah.' : null,
            ]);

            // Add Refinement if status is refinement
            if ($status === TicketStatus::REFINEMENT) {
                AssetItemRefinement::create([
                    'ticket_id' => $ticket->id,
                    'date' => Carbon::now()->subDays(rand(0, 5)),
                    'description' => 'Perbaikan komponen internal',
                    'note' => 'Menunggu suku cadang pengganti',
                    'result' => 'Sedang dikerjakan',
                ]);
            }
        }

        // --- SEED SPECIFIC DATA FOR SUPER ADMIN ---
        $superAdmin = User::where('email', 'superadmin@gmail.com')->first();
        if ($superAdmin) {
            $adminAssets = AssetItem::limit(3)->get();
            for ($i = 0; $i < 5; $i++) {
                Ticket::create([
                    'user_id' => $superAdmin->id,
                    'asset_item_id' => $adminAssets->random()->id,
                    'status' => collect([TicketStatus::PENDING, TicketStatus::PROCESS, TicketStatus::FINISH])->random(),
                    'priority' => collect(TicketPriority::cases())->random(),
                    'subject' => 'Kendala Admin: ' . $ticketCommonSubjects[array_rand($ticketCommonSubjects)],
                    'description' => 'Deskripsi tiket personal untuk Super Admin.',
                    'created_at' => Carbon::now()->subDays(rand(1, 5)),
                ]);
            }
        }
    }
}
