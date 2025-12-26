<?php

namespace Modules\Archieve\Enums;

enum ArchieveUserPermission: string
{
    // Category Permissions
    case ViewCategory = 'lihat_kategori_arsip';
    case ManageCategory = 'kelola_kategori_arsip';

    // Classification Permissions
    case ViewClassification = 'lihat_klasifikasi_arsip';
    case ManageClassification = 'kelola_klasifikasi_arsip';

    // Division Storage Permissions
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

    // Document Access Scopes (Hardcoded previously)
    case ViewAll = 'lihat_semua_arsip';
    case ViewDivision = 'lihat_arsip_divisi';
    case ViewPersonal = 'lihat_arsip_pribadi';
    case ManageAll = 'kelola_semua_arsip';
    case ManageDivision = 'kelola_arsip_divisi';

    // Search Scope Permissions (Used in DocumentService)
    case SearchDivisionScope = 'pencarian_dokumen_divisi';
    case SearchAllScope = 'pencarian_dokumen_keseluruhan';
    case SearchPersonalScope = 'pencarian_dokumen_pribadi';

    public static function values(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }
}
