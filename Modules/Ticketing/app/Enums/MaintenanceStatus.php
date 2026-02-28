<?php

namespace Modules\Ticketing\Enums;

enum MaintenanceStatus: string
{
    case PENDING = 'pending';
    case REFINEMENT = 'refinement';
    case FINISH = 'finish';
    case CONFIRMED = 'confirmed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Sedang Berjalan',
            self::REFINEMENT => 'Perbaikan',
            self::FINISH => 'Selesai',
            self::CONFIRMED => 'Terkonfirmasi',
            self::CANCELLED => 'Dibatalkan',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
