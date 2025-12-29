<?php

namespace Modules\VisitorManagement\Repositories\Visitor;

use Modules\VisitorManagement\Models\Visitor;
use Modules\VisitorManagement\Models\VisitorFeedbackRating;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EloquentVisitorRepository implements VisitorRepository
{
    public function findById(int $id): ?Visitor
    {
        return Visitor::find($id);
    }

    public function findByIdWith(int $id, array $relations): ?Visitor
    {
        return Visitor::with($relations)->find($id);
    }

    public function create(array $data): Visitor
    {
        return Visitor::create($data);
    }

    public function update(Visitor $visitor, array $data): bool
    {
        return $visitor->update($data);
    }

    public function delete(Visitor $visitor): bool
    {
        return $visitor->delete();
    }

    public function getActiveVisitors(): Collection
    {
        return Visitor::where('status', 'approved')
            ->whereNull('check_out_at')
            ->get();
    }

    public function getCheckOutList(int $limit = 50): Collection
    {
        return Visitor::whereIn('status', ['pending', 'approved', 'invited'])
            ->with(['division', 'purpose'])
            ->orderByRaw("FIELD(status, 'approved', 'pending', 'invited')")
            ->orderBy('check_in_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function searchCheckOut(string $query, int $limit = 10): Collection
    {
        return Visitor::whereIn('status', ['pending', 'approved', 'invited'])
            ->where(function($q) use ($query) {
                $q->where('visitor_name', 'like', "%{$query}%")
                  ->orWhere('organization', 'like', "%{$query}%")
                  ->orWhere('phone_number', 'like', "%{$query}%");
            })
            ->with(['division', 'purpose'])
            ->orderBy('status', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getPublicList(): Collection
    {
        return Visitor::whereIn('status', ['pending', 'approved', 'invited'])
            ->with(['division', 'purpose'])
            ->orderBy('check_in_at', 'desc')
            ->get();
    }

    public function getTodayCount(): int
    {
        return Visitor::whereDate('check_in_at', Carbon::today())->count();
    }

    public function getActiveCount(): int
    {
        return Visitor::where('status', 'approved')->whereNull('check_out_at')->count();
    }

    public function getTodayRejectedCount(): int
    {
        return Visitor::where('status', 'rejected')->whereDate('created_at', Carbon::today())->count();
    }

    public function getAverageRating(): float
    {
        return (float) (VisitorFeedbackRating::avg('rating') ?? 0);
    }

    public function getPurposeDistribution(): array
    {
        return DB::table('visitors')
            ->join('visitor_purposes', 'visitors.purpose_id', '=', 'visitor_purposes.id')
            ->select('visitor_purposes.name', DB::raw('count(*) as count'))
            ->groupBy('visitor_purposes.name')
            ->get()
            ->toArray();
    }

    public function getWeeklyTrendData(int $days = 7): array
    {
        return Visitor::select(DB::raw('DATE(check_in_at) as date'), DB::raw('count(*) as count'))
            ->where('check_in_at', '>=', Carbon::today()->subDays($days - 1))
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();
    }

    public function getMonthlyTrendData(int $months = 12): array
    {
        return Visitor::select(DB::raw("DATE_FORMAT(check_in_at, '%Y-%m') as month"), DB::raw('count(*) as count'))
            ->where('check_in_at', '>=', Carbon::today()->subMonths($months - 1)->startOfMonth())
            ->groupBy('month')
            ->pluck('count', 'month')
            ->toArray();
    }

    public function getRecentVisitors(int $limit = 5): array
    {
        return Visitor::with(['division', 'purpose'])
            ->orderBy('check_in_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($visitor) {
                return [
                    'id' => $visitor->id,
                    'name' => $visitor->visitor_name,
                    'organization' => $visitor->organization,
                    'division' => $visitor->division?->name,
                    'purpose' => $visitor->purpose?->name,
                    'status' => $visitor->status,
                    'check_in_at' => $visitor->check_in_at?->format('d M Y H:i'),
                ];
            })
            ->toArray();
    }

    public function getPendingVisitors(int $limit = 5): array
    {
        return Visitor::with(['division', 'purpose'])
            ->where('status', 'pending')
            ->orderBy('check_in_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($visitor) {
                return [
                    'id' => $visitor->id,
                    'name' => $visitor->visitor_name,
                    'organization' => $visitor->organization,
                    'division' => $visitor->division?->name,
                    'purpose' => $visitor->purpose?->name,
                    'check_in_at' => $visitor->check_in_at?->format('d M Y H:i'),
                ];
            })
            ->toArray();
    }
    public function getDatatableQuery(array $params): \Illuminate\Database\Eloquent\Builder
    {
        $query = Visitor::query();

        if (isset($params['visitor_name']) && $params['visitor_name'] != '') {
            $query->where('visitor_name', 'like', '%' . $params['visitor_name'] . '%');
        }

        if (isset($params['organization']) && $params['organization'] != '') {
            $query->where('organization', 'like', '%' . $params['organization'] . '%');
        }

        if (isset($params['status']) && $params['status'] != '') {
            $query->where('status', $params['status']);
        }

        if (isset($params['division_id']) && $params['division_id'] != '') {
            $query->where('division_id', $params['division_id']);
        }

        if (isset($params['search']) && $params['search'] != '') {
            $query->where(function ($q) use ($params) {
                $q->where('visitor_name', 'like', '%' . $params['search'] . '%')
                    ->orWhere('organization', 'like', '%' . $params['search'] . '%')
                    ->orWhere('phone_number', 'like', '%' . $params['search'] . '%');
            });
        }

        if (isset($params['sort_by']) && isset($params['sort_direction'])) {
            $query->orderBy($params['sort_by'], $params['sort_direction']);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query;
    }

    public function countByStatus(string $status, \Carbon\Carbon $startDate = null): int
    {
        $query = Visitor::where('status', $status);
        if ($startDate) {
            $query->where('check_in_at', '>=', $startDate);
        }
        return $query->count();
    }

    public function countByDate(\Carbon\Carbon $startDate): int
    {
        return Visitor::where('check_in_at', '>=', $startDate)->count();
    }

    public function getDetailedMonthlyTrend(int $year): array
    {
        return Visitor::select(
                DB::raw("DATE_FORMAT(check_in_at, '%Y-%m') as month"),
                DB::raw('count(*) as total_visitors'),
                DB::raw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed"),
                DB::raw("SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected")
            )
            ->whereYear('check_in_at', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->toArray();
    }

    public function getStatusDistribution(int $year): array
    {
        return Visitor::select('status', DB::raw('count(*) as count'))
            ->whereYear('check_in_at', $year)
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    public function getPeakHours(int $year): array
    {
        return Visitor::select(
                DB::raw("HOUR(check_in_at) as hour"),
                DB::raw('count(*) as count')
            )
            ->whereYear('check_in_at', $year)
            ->whereRaw('HOUR(check_in_at) >= 6')
            ->whereRaw('HOUR(check_in_at) <= 18')
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();
    }

    public function getBusiestDays(int $year): array
    {
        return Visitor::select(
                DB::raw('DAYOFWEEK(check_in_at) as day_num'),
                DB::raw('count(*) as count')
            )
            ->whereYear('check_in_at', $year)
            ->groupBy('day_num')
            ->orderBy('day_num')
            ->pluck('count', 'day_num')
            ->toArray();
    }

    public function getTopOrganizations(int $year, int $limit = 10): array
    {
        return Visitor::select('organization', DB::raw('count(*) as total'))
            ->whereYear('check_in_at', $year)
            ->whereNotNull('organization')
            ->where('organization', '!=', '')
            ->groupBy('organization')
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function getAverageDuration(int $year): int
    {
        return (int) (Visitor::whereYear('check_in_at', $year)
            ->whereNotNull('check_out_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, check_in_at, check_out_at)) as avg_duration')
            ->value('avg_duration') ?? 0);
    }

    public function getRepeatVisitors(int $year, int $limit = 10): array
    {
        return Visitor::select('visitor_name as name', 'phone_number as phone', DB::raw('count(*) as visit_count'))
            ->whereYear('check_in_at', $year)
            ->groupBy('visitor_name', 'phone_number')
            ->having('visit_count', '>', 1)
            ->orderByDesc('visit_count')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function getActiveVisitorsCount(): int
    {
        return Visitor::whereNotNull('check_in_at')
            ->whereNull('check_out_at')
            ->whereIn('status', ['approved', 'completed'])
            ->count();
    }

    public function getPurposeRankings(int $year, int $limit = 10, string $direction = 'desc'): array
    {
        return DB::table('visitors')
            ->join('visitor_purposes', 'visitors.purpose_id', '=', 'visitor_purposes.id')
            ->whereYear('visitors.check_in_at', $year)
            ->select('visitor_purposes.name', DB::raw('count(*) as total'))
            ->groupBy('visitor_purposes.name')
            ->orderBy('total', $direction)
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function getDivisionRankings(int $year, int $limit = 10, string $direction = 'desc'): array
    {
        return DB::table('visitors')
            ->join('divisions', 'visitors.division_id', '=', 'divisions.id')
            ->whereYear('visitors.check_in_at', $year)
            ->select('divisions.name', DB::raw('count(*) as total'))
            ->groupBy('divisions.name')
            ->orderBy('total', $direction)
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
