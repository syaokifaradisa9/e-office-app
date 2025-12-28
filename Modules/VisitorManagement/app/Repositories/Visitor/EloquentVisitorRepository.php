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
}
