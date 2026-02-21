<?php

namespace Modules\Ticketing\Enums;

enum TicketingPermission: string
{
    case ViewAssetCategoryDivisi = 'Lihat Data Kategori Asset Divisi';
    case ViewAllAssetCategory = 'Lihat Data Kategori Asset Keseluruhan';
    case ManageAssetCategory = 'Kelola Data Kategori Asset';
    case DeleteAssetCategory = 'Hapus Data Kategori Asset';

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
