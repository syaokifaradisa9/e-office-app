export enum ArchievePermission {
    // Category Permissions
    VIEW_CATEGORY = 'lihat_kategori_arsip',
    MANAGE_CATEGORY = 'kelola_kategori_arsip',

    // Classification Permissions
    VIEW_CLASSIFICATION = 'lihat_klasifikasi_arsip',
    MANAGE_CLASSIFICATION = 'kelola_klasifikasi_arsip',

    // Division Storage Permissions
    VIEW_DIVISION_STORAGE = 'lihat_penyimpanan_divisi',
    MANAGE_DIVISION_STORAGE = 'kelola_penyimpanan_divisi',

    // Dashboard Permissions
    VIEW_DASHBOARD_DIVISION = 'lihat_dashboard_arsip_divisi',
    VIEW_DASHBOARD_ALL = 'lihat_dashboard_arsip_keseluruhan',

    // Report Permissions
    VIEW_REPORT_DIVISION = 'lihat_laporan_arsip_divisi',
    VIEW_REPORT_ALL = 'lihat_laporan_arsip_keseluruhan',

    // Document Access Scopes
    VIEW_ALL = 'lihat_semua_arsip',
    VIEW_DIVISION = 'lihat_arsip_divisi',
    VIEW_PERSONAL = 'lihat_arsip_pribadi',
    MANAGE_ALL = 'kelola_semua_arsip',
    MANAGE_DIVISION = 'kelola_arsip_divisi',

    // Search Scope Permissions
    SEARCH_DIVISION_SCOPE = 'pencarian_dokumen_divisi',
    SEARCH_ALL_SCOPE = 'pencarian_dokumen_keseluruhan',
    SEARCH_PERSONAL_SCOPE = 'pencarian_dokumen_pribadi',
}
