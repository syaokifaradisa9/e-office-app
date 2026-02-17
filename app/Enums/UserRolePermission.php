<?php

namespace App\Enums;

enum UserRolePermission: string
{
    case VIEW_USER = 'lihat_pengguna';
    case MANAGE_USER = 'kelola_pengguna';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
