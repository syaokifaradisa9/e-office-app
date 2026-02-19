<?php

namespace Modules\Ticketing\Enums;

enum AssetModelType: string
{
    case Physic = 'Physic';
    case Digital = 'Digital';

    public static function values(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }
}
