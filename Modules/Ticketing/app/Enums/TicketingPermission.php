<?php

namespace Modules\Ticketing\Enums;

enum TicketingPermission: string
{
    case ViewAssetModelDivisi = 'Lihat Data Asset Model Divisi';
    case ViewAllAssetModel = 'Lihat Data Asset Model Keseluruhan';
    case ManageAssetModel = 'Kelola Data Asset Model';
    case DeleteAssetModel = 'Hapus Data Asset Model';

    case ViewChecklist = 'Lihat Data Checklist';
    case ManageChecklist = 'Kelola Data Checklist';

    case ViewPersonalAsset = 'Lihat Data Asset Pribadi';
    case ViewDivisionAsset = 'Lihat Data Asset Divisi';
    case ViewAllAsset = 'Lihat Data Asset Keseluruhan';
    case ManageAsset = 'Kelola Data Asset';
    case DeleteAsset = 'Hapus Data Asset';

    case ViewDivisionMaintenance = 'Lihat Jadwal Maintenance Divisi';
    case ViewAllMaintenance = 'Lihat Jadwal Maintenance Keseluruhan';
    case ProsesMaintenance = 'Proses Maintenance';
    case ConfirmMaintenance = 'Konfirmasi Proses Maintenance';

    public static function values(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }
}
