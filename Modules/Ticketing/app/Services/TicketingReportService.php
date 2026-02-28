<?php

namespace Modules\Ticketing\Services;

use App\Models\User;
use App\Models\Division;
use Illuminate\Support\Facades\DB;
use Modules\Ticketing\Models\Ticket;
use Modules\Ticketing\Models\AssetItem;
use Modules\Ticketing\Models\Maintenance;
use Modules\Ticketing\Enums\TicketStatus;
use Modules\Ticketing\Enums\MaintenanceStatus;
use Modules\Ticketing\Enums\AssetItemStatus;
use Carbon\Carbon;

class TicketingReportService
{
    public function getDivisionReportData(User $user): array
    {
        $divisionId = $user->division_id;
        $currentYear = now()->year;

        // 1. Compliance Rate & Total Assets
        $totalAssets = AssetItem::where('division_id', $divisionId)->count();
        $problemAssetsCount = AssetItem::where('division_id', $divisionId)->whereIn('status', [AssetItemStatus::Refinement->value, AssetItemStatus::Damaged->value])->count();
        $totalMaintenance = Maintenance::whereHas('assetItem', fn($q) => $q->where('division_id', $divisionId))->count();
        $confirmedMaintenance = Maintenance::whereHas('assetItem', fn($q) => $q->where('division_id', $divisionId))
            ->where('status', MaintenanceStatus::CONFIRMED->value)
            ->count();

        // 2. Average Handling Time (Tickets)
        // Let's use closed_at - created_at or finished_at - created_at
        $driver = config('database.default');
        if ($driver === 'sqlite') {
            $avgHandlingTime = Ticket::whereHas('user', fn($q) => $q->where('division_id', $divisionId))
                ->whereNotNull('finished_at')
                ->select(DB::raw('AVG(julianday(finished_at) - julianday(created_at)) as avg_days'))
                ->value('avg_days');
        } else {
            $avgHandlingTime = Ticket::whereHas('user', fn($q) => $q->where('division_id', $divisionId))
                ->whereNotNull('finished_at')
                ->select(DB::raw('AVG(DATEDIFF(finished_at, created_at)) as avg_days'))
                ->value('avg_days');
        }

        // Ratings
        $avgTicketRating = Ticket::whereHas('user', fn($q) => $q->where('division_id', $divisionId))->avg('rating');
        $avgMaintenanceRating = Maintenance::whereHas('assetItem', fn($q) => $q->where('division_id', $divisionId))->avg('rating');

        // 3. Top 5 Assets (Tickets + Maintenances)
        $topProblemAssets = AssetItem::where('division_id', $divisionId)
            ->withCount(['tickets'])
            ->withCount(['maintenances as refinement_maintenances_count' => fn($q) => $q->where('status', MaintenanceStatus::REFINEMENT->value)])
            ->get()
            ->sortByDesc(fn($asset) => $asset->tickets_count + $asset->refinement_maintenances_count)
            ->take(5)
            ->values();

        // 4. Top 5 Users with problematic assets (count tickets by user)
        $topUsers = User::where('division_id', $divisionId)
            ->withCount('tickets')
            ->orderByDesc('tickets_count')
            ->limit(5)
            ->get();

        // 5. Monthly Trend
        $format = $driver === 'sqlite' ? 'strftime("%m", created_at)' : 'MONTH(created_at)';
        $monthlyTrend = Ticket::whereHas('user', fn($q) => $q->where('division_id', $divisionId))
            ->whereYear('created_at', $currentYear)
            ->select(DB::raw("$format as month"), DB::raw('count(*) as total'))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // 6. Priority Distribution
        $priorityOrder = ['low' => 1, 'medium' => 2, 'high' => 3];
        $priorityStats = Ticket::whereHas('user', fn($q) => $q->where('division_id', $divisionId))
            ->select('priority', DB::raw('count(*) as total'))
            ->groupBy('priority')
            ->get()
            ->sortBy(function($item) use ($priorityOrder) {
                $priorityValue = $item->priority instanceof \BackedEnum ? $item->priority->value : $item->priority;
                return $priorityOrder[strtolower($priorityValue)] ?? 99;
            })
            ->values();

        // 7. Merk & Model Distribution
        $merkStats = AssetItem::where('division_id', $divisionId)
            ->with('assetCategory')
            ->get()
            ->groupBy(function($item) {
                return trim(($item->assetCategory ? $item->assetCategory->name : '') . ' ' . $item->merk);
            })
            ->map(function($items, $key) {
                return [
                    'merk' => $key,
                    'total' => $items->count()
                ];
            })
            ->sortByDesc('total')
            ->values();

        $modelStats = AssetItem::where('division_id', $divisionId)
            ->with('assetCategory')
            ->get()
            ->groupBy(function($item) {
                return trim(($item->assetCategory ? $item->assetCategory->name : '') . ' ' . $item->merk . ' ' . $item->model);
            })
            ->map(function($items, $key) {
                return [
                    'model' => $key,
                    'total' => $items->count()
                ];
            })
            ->sortByDesc('total')
            ->values();

        return [
            'division_name' => $user->division?->name,
            'metrics' => [
                'total_assets' => $totalAssets,
                'problem_assets' => $problemAssetsCount,
                'compliance_rate' => $totalMaintenance > 0 ? round(($confirmedMaintenance / $totalMaintenance) * 100, 1) : 0,
                'avg_handling_days' => round((float)$avgHandlingTime, 1),
                'avg_ticket_rating' => round((float)$avgTicketRating, 1),
                'avg_maintenance_rating' => round((float)$avgMaintenanceRating, 1),
            ],
            'top_problem_assets' => $topProblemAssets,
            'top_users' => $topUsers,
            'monthly_trend' => $monthlyTrend,
            'priority_stats' => $priorityStats,
            'merk_stats' => $merkStats,
            'model_stats' => $modelStats,
        ];
    }

    public function getAllReportData(): array
    {
        $currentYear = now()->year;

        // 1. Compliance Rate & Total Assets
        $totalAssets = AssetItem::count();
        $problemAssetsCount = AssetItem::whereIn('status', [AssetItemStatus::Refinement->value, AssetItemStatus::Damaged->value])->count();
        $totalMaintenance = Maintenance::count();
        $confirmedMaintenance = Maintenance::where('status', MaintenanceStatus::CONFIRMED->value)->count();

        $totalTickets = Ticket::count();
        $assetsWithTickets = Ticket::select('asset_item_id')->whereNotNull('asset_item_id')->distinct()->count();
        $statusOrder = [
            TicketStatus::PENDING->value => 1,
            TicketStatus::PROCESS->value => 2,
            TicketStatus::REFINEMENT->value => 3,
            TicketStatus::FINISH->value => 4,
            TicketStatus::CLOSED->value => 5,
        ];

        $ticketStatusStats = Ticket::select('status', DB::raw('count(*) as total'))
            ->where('status', '!=', TicketStatus::DAMAGED->value)
            ->groupBy('status')
            ->get()
            ->sortBy(function($item) use ($statusOrder) {
                $statusValue = $item->status instanceof \BackedEnum ? $item->status->value : $item->status;
                return $statusOrder[strtolower($statusValue)] ?? 99;
            })
            ->values();

        // 2. Average Handling Time (Tickets)
        $driver = config('database.default');
        if ($driver === 'sqlite') {
            $avgHandlingTime = Ticket::whereNotNull('finished_at')
                ->select(DB::raw('AVG(julianday(finished_at) - julianday(created_at)) as avg_days'))
                ->value('avg_days');
        } else {
            $avgHandlingTime = Ticket::whereNotNull('finished_at')
                ->select(DB::raw('AVG(DATEDIFF(finished_at, created_at)) as avg_days'))
                ->value('avg_days');
        }

        // Ratings
        $avgTicketRating = Ticket::avg('rating');
        $avgMaintenanceRating = Maintenance::avg('rating');

        // 3. Top 5 Assets (Tickets + Maintenances)
        $topProblemAssets = AssetItem::withCount(['tickets'])
            ->withCount(['maintenances as refinement_maintenances_count' => fn($q) => $q->where('status', MaintenanceStatus::REFINEMENT->value)])
            ->get()
            ->sortByDesc(fn($asset) => $asset->tickets_count + $asset->refinement_maintenances_count)
            ->take(5)
            ->values();

        // 4. Top 5 Users with problematic assets (count tickets by user)
        $topUsers = User::withCount('tickets')
            ->orderByDesc('tickets_count')
            ->limit(5)
            ->get();

        // 5. Monthly Trend (Tickets)
        $format = $driver === 'sqlite' ? 'strftime("%m", created_at)' : 'MONTH(created_at)';
        $monthlyTrend = Ticket::whereYear('created_at', $currentYear)
            ->select(DB::raw("$format as month"), DB::raw('count(*) as total'))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Maintenance Monthly Trend
        $maintenanceFormat = $driver === 'sqlite' ? 'strftime("%m", estimation_date)' : 'MONTH(estimation_date)';
        $maintenanceMonthlyTrend = Maintenance::whereYear('estimation_date', $currentYear)
            ->select(DB::raw("$maintenanceFormat as month"), DB::raw('count(*) as total'))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Maintenance Problem Assets
        $maintenanceProblemAssets = AssetItem::whereHas('maintenances', function($q) {
            $q->has('refinements');
        })->with(['users', 'division', 'maintenances' => function($q) {
            $q->has('refinements')->with('refinements');
        }])->get()->map(function($asset) {
            $problems = collect();
            foreach ($asset->maintenances as $maintenance) {
                foreach ($maintenance->refinements as $refinement) {
                    if ($refinement->description) {
                        $problems->push($refinement->description);
                    }
                }
            }
            return [
                'merk' => $asset->merk,
                'model' => $asset->model,
                'serial_number' => $asset->serial_number,
                'user' => $asset->users->pluck('name')->join(', ') ?: '-',
                'division' => $asset->division ? $asset->division->name : '-',
                'problems' => $problems->unique()->values()->all()
            ];
        });

        // 6. Priority Distribution
        $priorityOrder = ['low' => 1, 'medium' => 2, 'high' => 3];
        $priorityStats = Ticket::select('priority', DB::raw('count(*) as total'))
            ->groupBy('priority')
            ->get()
            ->sortBy(function($item) use ($priorityOrder) {
                $priorityValue = $item->priority instanceof \BackedEnum ? $item->priority->value : $item->priority;
                return $priorityOrder[strtolower($priorityValue)] ?? 99;
            })
            ->values();

        // 7. Merk & Model Distribution
        $assetItems = AssetItem::with(['assetCategory', 'division'])->get();

        $merkStats = $assetItems->groupBy(function($item) {
                return trim(($item->assetCategory ? $item->assetCategory->name : '') . ' ' . $item->merk);
            })
            ->map(function($items, $key) {
                return [
                    'merk' => $key,
                    'total' => $items->count()
                ];
            })
            ->sortByDesc('total')
            ->values();

        $modelStats = $assetItems->groupBy(function($item) {
                return trim(($item->assetCategory ? $item->assetCategory->name : '') . ' ' . $item->merk . ' ' . $item->model);
            })
            ->map(function($items, $key) {
                return [
                    'model' => $key,
                    'total' => $items->count()
                ];
            })
            ->sortByDesc('total')
            ->values();

        $merkStatsByDivision = $assetItems->whereNotNull('division_id')->groupBy('division.name')->map(function($divisionItems) {
            return $divisionItems->groupBy(function($item) {
                return trim(($item->assetCategory ? $item->assetCategory->name : '') . ' ' . $item->merk);
            })->map(function($items, $key) {
                return [
                    'merk' => $key,
                    'total' => $items->count()
                ];
            })->sortByDesc('total')->values();
        });

        $modelStatsByDivision = $assetItems->whereNotNull('division_id')->groupBy('division.name')->map(function($divisionItems) {
            return $divisionItems->groupBy(function($item) {
                return trim(($item->assetCategory ? $item->assetCategory->name : '') . ' ' . $item->merk . ' ' . $item->model);
            })->map(function($items, $key) {
                return [
                    'model' => $key,
                    'total' => $items->count()
                ];
            })->sortByDesc('total')->values();
        });

        $maintenanceStatsByDivision = \App\Models\Division::all()->mapWithKeys(function ($division) use ($currentYear, $driver, $maintenanceProblemAssets) {
            $divisionAssets = AssetItem::where('division_id', $division->id)->pluck('id');
            $divisionMaintenancesQuery = Maintenance::whereIn('asset_item_id', $divisionAssets);
            
            $totalMaintenance = $divisionMaintenancesQuery->count();
            $confirmedMaintenance = (clone $divisionMaintenancesQuery)->where('status', \Modules\Ticketing\Enums\MaintenanceStatus::CONFIRMED->value)->count();
            
            $avgMaintenanceRating = (clone $divisionMaintenancesQuery)->avg('rating') ?? 0;
            
            $maintenanceFormat = $driver === 'sqlite' ? 'strftime("%m", estimation_date)' : 'MONTH(estimation_date)';
            $monthlyTrend = (clone $divisionMaintenancesQuery)->whereYear('estimation_date', $currentYear)
                ->select(DB::raw("$maintenanceFormat as month"), DB::raw('count(*) as total'))
                ->groupBy('month')
                ->orderBy('month')
                ->get();
                
            return [$division->name => [
                'metrics' => [
                    'compliance_rate' => $totalMaintenance > 0 ? round(($confirmedMaintenance / $totalMaintenance) * 100, 1) : 0,
                    'total_maintenances' => $totalMaintenance,
                    'confirmed_maintenances' => $confirmedMaintenance,
                    'avg_maintenance_rating' => round((float)$avgMaintenanceRating, 1),
                ],
                'monthly_trend' => $monthlyTrend,
                'problem_assets' => collect($maintenanceProblemAssets)->where('division', $division->name)->values()
            ]];
        });

        $ticketStatsByDivision = \App\Models\Division::all()->mapWithKeys(function ($division) use ($currentYear, $driver, $statusOrder) {
            $divisionTicketsQuery = Ticket::whereHas('user', fn($q) => $q->where('division_id', $division->id));

            $totalTickets = $divisionTicketsQuery->count();
            $assetsWithTicketsCount = (clone $divisionTicketsQuery)->select('asset_item_id')->whereNotNull('asset_item_id')->distinct()->count();
            $avgTicketRating = (clone $divisionTicketsQuery)->avg('rating') ?? 0;

            $format = $driver === 'sqlite' ? 'strftime("%m", created_at)' : 'MONTH(created_at)';
            $monthlyTrend = (clone $divisionTicketsQuery)->whereYear('created_at', $currentYear)
                ->select(DB::raw("$format as month"), DB::raw('count(*) as total'))
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            $priorityOrder = ['low' => 1, 'medium' => 2, 'high' => 3];
            $priorityStats = (clone $divisionTicketsQuery)->select('priority', DB::raw('count(*) as total'))
                ->groupBy('priority')
                ->get()
                ->sortBy(function($item) use ($priorityOrder) {
                    $priorityValue = $item->priority instanceof \BackedEnum ? $item->priority->value : $item->priority;
                    return $priorityOrder[strtolower($priorityValue)] ?? 99;
                })
                ->values();

            $statusStats = (clone $divisionTicketsQuery)->select('status', DB::raw('count(*) as total'))
                ->where('status', '!=', TicketStatus::DAMAGED->value)
                ->groupBy('status')
                ->get()
                ->sortBy(function($item) use ($statusOrder) {
                    $statusValue = $item->status instanceof \BackedEnum ? $item->status->value : $item->status;
                    return $statusOrder[strtolower($statusValue)] ?? 99;
                })
                ->values();

            $topProblemAssets = AssetItem::where('division_id', $division->id)
                ->withCount(['tickets'])
                ->having('tickets_count', '>', 0)
                ->orderByDesc('tickets_count')
                ->take(5)
                ->get();

            return [$division->name => [
                'metrics' => [
                    'total_tickets' => $totalTickets,
                    'assets_with_tickets' => $assetsWithTicketsCount,
                    'avg_ticket_rating' => round((float)$avgTicketRating, 1),
                ],
                'monthly_trend' => $monthlyTrend,
                'priority_stats' => $priorityStats,
                'status_stats' => $statusStats,
                'top_problem_assets' => $topProblemAssets,
            ]];
        });

        return [
            'metrics' => [
                'total_assets' => $totalAssets,
                'problem_assets' => $problemAssetsCount,
                'total_tickets' => $totalTickets,
                'assets_with_tickets' => $assetsWithTickets,
                'total_maintenances' => $totalMaintenance,
                'confirmed_maintenances' => $confirmedMaintenance,
                'compliance_rate' => $totalMaintenance > 0 ? round(($confirmedMaintenance / $totalMaintenance) * 100, 1) : 0,
                'avg_handling_days' => round((float)$avgHandlingTime, 1),
                'avg_ticket_rating' => round((float)$avgTicketRating, 1),
                'avg_maintenance_rating' => round((float)$avgMaintenanceRating, 1),
            ],
            'top_problem_assets' => $topProblemAssets,
            'top_users' => $topUsers,
            'monthly_trend' => $monthlyTrend,
            'priority_stats' => $priorityStats,
            'ticket_status_stats' => $ticketStatusStats,
            'merk_stats' => $merkStats,
            'model_stats' => $modelStats,
            'merk_stats_by_division' => $merkStatsByDivision,
            'model_stats_by_division' => $modelStatsByDivision,
            'maintenance_monthly_trend' => $maintenanceMonthlyTrend,
            'maintenance_problem_assets' => $maintenanceProblemAssets,
            'maintenance_stats_by_division' => $maintenanceStatsByDivision,
            'ticket_stats_by_division' => $ticketStatsByDivision,
        ];
    }
}
