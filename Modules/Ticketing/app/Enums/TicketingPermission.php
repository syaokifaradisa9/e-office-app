<?php

namespace Modules\Ticketing\Enums;

enum TicketingPermission: string
{
    case ViewAssetModelDivisi = 'Lihat Data Asset Model Divisi';
    case ViewAllAssetModel = 'Lihat Data Asset Model Keseluruhan';
    case ManageAssetModel = 'Kelola Data Asset Model';
    case DeleteAssetModel = 'Hapus Data Asset Model';

    public static function values(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }
}
