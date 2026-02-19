export enum VisitorPermission {
    // Dashboard
    VIEW_DASHBOARD = 'lihat_dashboard_pengunjung',

    // Master Data (Purposes)
    VIEW_MASTER = 'lihat_master_manajemen_pengunjung',
    MANAGE_MASTER = 'kelola_master_manajemen_pengunjung',

    // Master Data (Feedback Questions)
    VIEW_FEEDBACK_QUESTION = 'lihat_pertanyaan_feedback',
    MANAGE_FEEDBACK_QUESTION = 'kelola_pertanyaan_feedback',

    // Visitor Operations
    VIEW_DATA = 'lihat_data_pengunjung',
    CONFIRM_VISIT = 'konfirmasi_kunjungan',
    CREATE_INVITATION = 'buat_undangan_tamu',

    // Criticism & Suggestions
    VIEW_CRITICISM_FEEDBACK = 'lihat_kritik_saran_pengunjung',
    MANAGE_CRITICISM_FEEDBACK = 'kelola_kritik_saran_pengunjung',

    // Reports
    VIEW_REPORT = 'lihat_laporan_pengunjung',
}
