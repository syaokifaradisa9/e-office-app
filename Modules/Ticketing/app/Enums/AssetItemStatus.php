<?php

namespace Modules\Ticketing\Enums;

enum AssetItemStatus: string
{
    case Available = 'Available';
    case Refinement = 'Refinement';
    case Damaged = 'Damaged';

    public function label(): string
    {
        return match($this) {
            self::Available => 'Tersedia',
            self::Refinement => 'Perbaikan',
            self::Damaged => 'Rusak',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Available => 'success',
            self::Refinement => 'warning',
            self::Damaged => 'danger',
        };
    }
}
