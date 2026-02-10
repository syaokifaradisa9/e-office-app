<?php

namespace Modules\Inventory\Repositories\StockOpname;

use Carbon\Carbon;
use Modules\Inventory\Models\StockOpname;

class EloquentStockOpnameRepository implements StockOpnameRepository
{
    public function hasOpnameThisMonth(?int $divisionId = null): bool
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $query = StockOpname::whereMonth('opname_date', $currentMonth)
            ->whereYear('opname_date', $currentYear);

        if ($divisionId) {
            $query->where('division_id', $divisionId);
        } else {
            $query->whereNull('division_id');
        }

        return $query->exists();
    }

    public function hasActiveOpname(?int $divisionId = null): bool
    {
        $query = StockOpname::whereIn('status', ['Pending', 'Proses']);

        if ($divisionId) {
            $query->where('division_id', $divisionId);
        } else {
            $query->whereNull('division_id');
        }

        return $query->exists();
    }

    public function create(array $data): StockOpname
    {
        return StockOpname::create($data);
    }

    public function findById(int $id): ?StockOpname
    {
        return StockOpname::find($id);
    }
}
