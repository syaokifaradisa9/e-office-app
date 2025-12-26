<?php

namespace Modules\Archieve\Enums;

enum ArchievePermission: string
{
    case ViewCategory = 'lihat_kategori_arsip';
    case ManageCategory = 'kelola_kategori_arsip';
    case ViewClassification = 'lihat_klasifikasi_arsip';
    case ManageClassification = 'kelola_klasifikasi_arsip';
    case ViewDivisionStorage = 'lihat_penyimpanan_divisi';
    case ManageDivisionStorage = 'kelola_penyimpanan_divisi';

    // Dashboard Permissions
    case ViewDashboardDivision = 'lihat_dashboard_arsip_divisi';
    case ViewDashboardAll = 'lihat_dashboard_arsip_keseluruhan';

    // Report Permissions
    case ViewReportDivision = 'lihat_laporan_arsip_divisi';
    case ViewReportAll = 'lihat_laporan_arsip_keseluruhan';

    // Document Permissions
    case ViewDocument = 'lihat_arsip_dokumen';
    case ManageDocument = 'kelola_arsip_dokumen';
    case SearchDocument = 'cari_arsip_dokumen';

    public static function values(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }
}
