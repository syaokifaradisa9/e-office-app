<?php

namespace App\Enums;

enum DivisionRolePermission: string
{
    case VIEW_DIVISION = 'lihat_divisi';
    case MANAGE_DIVISION = 'kelola_divisi';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
