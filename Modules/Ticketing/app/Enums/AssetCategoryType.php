<?php

namespace Modules\Ticketing\Enums;

enum AssetCategoryType: string
{
    case Physic = 'Physic';
    case Digital = 'Digital';

    public function label(): string
    {
        return match ($this) {
            self::Physic => 'Fisik',
            self::Digital => 'Digital',
        };
    }

    public static function values(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }
}
