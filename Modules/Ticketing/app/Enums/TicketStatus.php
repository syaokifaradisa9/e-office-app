<?php

namespace Modules\Ticketing\Enums;

enum TicketStatus: string
{
    case PENDING = 'pending';
    case PROCESS = 'process';
    case FINISH = 'finish';
    case REFINEMENT = 'refinement';
    case DAMAGED = 'damaged';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PROCESS => 'Proses',
            self::FINISH => 'Selesai',
            self::REFINEMENT => 'Perbaikan',
            self::DAMAGED => 'Rusak Total',
            self::CLOSED => 'Ditutup',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
