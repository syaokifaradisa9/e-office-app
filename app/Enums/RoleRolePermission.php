<?php

namespace App\Enums;

enum RoleRolePermission: string
{
    case VIEW_ROLE = 'lihat_role';
    case MANAGE_ROLE = 'kelola_role';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
