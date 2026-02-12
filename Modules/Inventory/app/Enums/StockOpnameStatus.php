<?php

namespace Modules\Inventory\Enums;

enum StockOpnameStatus: string
{
    case Pending = 'Pending';
    case Proses = 'Proses';
    case StockOpname = 'Stock Opname';
    case Finish = 'Finish';

    public static function values(): array
    {
        return array_map(fn($status) => $status->value, self::cases());
    }
}
