<?php

namespace App\Enums;

enum PositionRolePermission: string
{
    case VIEW_POSITION = 'lihat_jabatan';
    case MANAGE_POSITION = 'kelola_jabatan';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
