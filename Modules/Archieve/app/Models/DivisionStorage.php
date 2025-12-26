<?php

namespace Modules\Archieve\Models;

use App\Models\Division;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DivisionStorage extends Model
{
    protected $table = 'archieve_division_storages';

    protected $fillable = [
        'division_id',
        'max_size',
        'used_size',
    ];

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    /**
     * Get human readable max size.
     */
    public function getMaxSizeLabelAttribute(): string
    {
        return $this->formatBytes($this->max_size);
    }

    /**
     * Get human readable used size.
     */
    public function getUsedSizeLabelAttribute(): string
    {
        return $this->formatBytes($this->used_size);
    }

    /**
     * Get usage percentage.
     */
    public function getUsagePercentageAttribute(): float
    {
        if ($this->max_size <= 0) return 0;
        return round(($this->used_size / $this->max_size) * 100, 2);
    }

    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
