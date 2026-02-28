<?php

namespace Modules\Ticketing\Services;

use Modules\Ticketing\Models\Ticket;
use Modules\Ticketing\Models\Maintenance;
use Modules\Ticketing\Models\AssetItem;
use Modules\Ticketing\Enums\TicketingPermission;
use Modules\Ticketing\Enums\TicketStatus;
use Modules\Ticketing\Enums\MaintenanceStatus;
use Illuminate\Support\Facades\DB;

class TicketingDashboardService
{
    public function getDashboardTabs(): array
    {
        $user = auth()->user();
        $tabs = [];

        // Tab: Pribadi
        if ($user->can(TicketingPermission::ViewPersonalDashboard->value)) {
            $tabs[] = [
                'id' => 'personal_ticketing',
                'label' => 'Ticketing Pribadi',
                'icon' => 'user',
                'type' => 'ticketing',
                'data' => $this->getPersonalData($user),
            ];
        }

        // Tab: Divisi
        if ($user->can(TicketingPermission::ViewDivisionDashboard->value) && $user->division_id) {
            $tabs[] = [
                'id' => 'division_ticketing',
                'label' => 'Ticketing ' . ($user->division?->name ?? 'Divisi'),
                'icon' => 'building',
                'type' => 'ticketing',
                'data' => $this->getDivisionData($user),
            ];
        }

        // Tab: Keseluruhan
        if ($user->can(TicketingPermission::ViewAllDashboard->value)) {
            $tabs[] = [
                'id' => 'all_ticketing',
                'label' => 'Keseluruhan Ticketing',
                'icon' => 'globe',
                'type' => 'ticketing',
                'data' => $this->getAllData(),
            ];
        }

        return $tabs;
    }

    private function getPersonalData($user): array
    {
        $currentYear = now()->year;

        // 1. Total Assets linked to user
        $assetCount = AssetItem::whereHas('users', fn($q) => $q->where('user_id', $user->id))->count();

        // 2. Remaining maintenance this year (Estimation Date >= today, Year = current, Status != Confirmed/Cancelled)
        $remainingMaintenanceCount = Maintenance::whereHas('assetItem.users', fn($q) => $q->where('user_id', $user->id))
            ->whereYear('estimation_date', $currentYear)
            ->where('estimation_date', '>=', now()->toDateString())
            ->whereNotIn('status', [MaintenanceStatus::CONFIRMED->value, MaintenanceStatus::CANCELLED->value])
            ->count();

        // 3. Total Tickets this year
        $ticketsThisYearCount = Ticket::where('user_id', $user->id)
            ->whereYear('created_at', $currentYear)
            ->count();

        // 4. Assets Under Repair (Tickets in process/refinement OR Maintenance in refinement)
        $assetsUnderRepairCount = AssetItem::whereHas('users', fn($q) => $q->where('user_id', $user->id))
            ->where(function($query) {
                $query->whereHas('tickets', fn($q) => $q->whereIn('status', [TicketStatus::PROCESS->value, TicketStatus::REFINEMENT->value]))
                      ->orWhereHas('maintenances', fn($q) => $q->where('status', MaintenanceStatus::REFINEMENT->value));
            })->count();

        // List 5 Nearest Maintenance
        $nearestMaintenances = Maintenance::whereHas('assetItem.users', fn($q) => $q->where('user_id', $user->id))
            ->where('estimation_date', '>=', now()->toDateString())
            ->whereNotIn('status', [MaintenanceStatus::CONFIRMED->value, MaintenanceStatus::CANCELLED->value])
            ->with(['assetItem.assetCategory'])
            ->orderBy('estimation_date', 'asc')
            ->limit(5)
            ->get();

        // List 5 Recent Active Tickets
        $recentActiveTickets = Ticket::where('user_id', $user->id)
            ->whereIn('status', [TicketStatus::PENDING->value, TicketStatus::PROCESS->value, TicketStatus::REFINEMENT->value])
            ->with(['assetItem.assetCategory'])
            ->latest()
            ->limit(5)
            ->get();

        return [
            'stats' => [
                'total_assets' => $assetCount,
                'remaining_maintenance' => $remainingMaintenanceCount,
                'tickets_this_year' => $ticketsThisYearCount,
                'assets_under_repair' => $assetsUnderRepairCount,
            ],
            'nearest_maintenances' => $nearestMaintenances,
            'recent_active_tickets' => $recentActiveTickets,
        ];
    }

    private function getDivisionData($user): array
    {
        $divisionId = $user->division_id;
        $currentYear = now()->year;

        // 1. Total Assets in division
        $assetCount = AssetItem::where('division_id', $divisionId)->count();

        // 2. Remaining maintenance this year for division
        $remainingMaintenanceCount = Maintenance::whereHas('assetItem', fn($q) => $q->where('division_id', $divisionId))
            ->whereYear('estimation_date', $currentYear)
            ->where('estimation_date', '>=', now()->toDateString())
            ->whereNotIn('status', [MaintenanceStatus::CONFIRMED->value, MaintenanceStatus::CANCELLED->value])
            ->count();

        // 3. Total Tickets this year in division
        $ticketsThisYearCount = Ticket::whereHas('user', fn($q) => $q->where('division_id', $divisionId))
            ->whereYear('created_at', $currentYear)
            ->count();

        // 4. Assets Under Repair in division
        $assetsUnderRepairCount = AssetItem::where('division_id', $divisionId)
            ->where(function ($query) {
                $query->whereHas('tickets', fn($q) => $q->whereIn('status', [TicketStatus::PROCESS->value, TicketStatus::REFINEMENT->value]))
                    ->orWhereHas('maintenances', fn($q) => $q->where('status', MaintenanceStatus::REFINEMENT->value));
            })->count();

        // List 5 Nearest Maintenance for division
        $nearestMaintenances = Maintenance::whereHas('assetItem', fn($q) => $q->where('division_id', $divisionId))
            ->where('estimation_date', '>=', now()->toDateString())
            ->whereNotIn('status', [MaintenanceStatus::CONFIRMED->value, MaintenanceStatus::CANCELLED->value])
            ->with(['assetItem.assetCategory', 'assetItem.users'])
            ->orderBy('estimation_date', 'asc')
            ->limit(5)
            ->get();

        // List 5 Recent Active Tickets for division
        $recentActiveTickets = Ticket::whereHas('user', fn($q) => $q->where('division_id', $divisionId))
            ->whereIn('status', [TicketStatus::PENDING->value, TicketStatus::PROCESS->value, TicketStatus::REFINEMENT->value])
            ->with(['user', 'assetItem.assetCategory'])
            ->latest()
            ->limit(5)
            ->get();

        return [
            'division_name' => $user->division?->name,
            'stats' => [
                'total_assets' => $assetCount,
                'remaining_maintenance' => $remainingMaintenanceCount,
                'tickets_this_year' => $ticketsThisYearCount,
                'assets_under_repair' => $assetsUnderRepairCount,
            ],
            'nearest_maintenances' => $nearestMaintenances,
            'recent_active_tickets' => $recentActiveTickets,
        ];
    }

    private function getAllData(): array
    {
        $currentYear = now()->year;

        // 1. Total Assets Global
        $assetCount = AssetItem::count();

        // 2. Remaining maintenance this year Global
        $remainingMaintenanceCount = Maintenance::whereYear('estimation_date', $currentYear)
            ->where('estimation_date', '>=', now()->toDateString())
            ->whereNotIn('status', [MaintenanceStatus::CONFIRMED->value, MaintenanceStatus::CANCELLED->value])
            ->count();

        // 3. Total Tickets this year Global
        $ticketsThisYearCount = Ticket::whereYear('created_at', $currentYear)
            ->count();

        // 4. Assets Under Repair Global
        $assetsUnderRepairCount = AssetItem::where(function ($query) {
            $query->whereHas('tickets', fn($q) => $q->whereIn('status', [TicketStatus::PROCESS->value, TicketStatus::REFINEMENT->value]))
                ->orWhereHas('maintenances', fn($q) => $q->where('status', MaintenanceStatus::REFINEMENT->value));
        })->count();

        // List 5 Nearest Maintenance Global
        $nearestMaintenances = Maintenance::where('estimation_date', '>=', now()->toDateString())
            ->whereNotIn('status', [MaintenanceStatus::CONFIRMED->value, MaintenanceStatus::CANCELLED->value])
            ->with(['assetItem.assetCategory', 'assetItem.users'])
            ->orderBy('estimation_date', 'asc')
            ->limit(5)
            ->get();

        // List 5 Recent Active Tickets Global
        $recentActiveTickets = Ticket::whereIn('status', [TicketStatus::PENDING->value, TicketStatus::PROCESS->value, TicketStatus::REFINEMENT->value])
            ->with(['user', 'assetItem.assetCategory'])
            ->latest()
            ->limit(5)
            ->get();

        return [
            'stats' => [
                'total_assets' => $assetCount,
                'remaining_maintenance' => $remainingMaintenanceCount,
                'tickets_this_year' => $ticketsThisYearCount,
                'assets_under_repair' => $assetsUnderRepairCount,
            ],
            'nearest_maintenances' => $nearestMaintenances,
            'recent_active_tickets' => $recentActiveTickets,
            'average_rating' => round(Ticket::whereNotNull('rating')->avg('rating'), 1) ?? 0,
        ];
    }
}
