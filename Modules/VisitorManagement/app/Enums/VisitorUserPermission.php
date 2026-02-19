<?php

namespace Modules\VisitorManagement\Enums;

enum VisitorUserPermission: string
{
    // Dashboard
    case ViewDashboard = 'lihat_dashboard_pengunjung';

    // Master Data (Purposes)
    case ViewMaster = 'lihat_master_manajemen_pengunjung';
    case ManageMaster = 'kelola_master_manajemen_pengunjung';

    // Master Data (Feedback Questions)
    case ViewFeedbackQuestion = 'lihat_pertanyaan_feedback';
    case ManageFeedbackQuestion = 'kelola_pertanyaan_feedback';

    // Visitor Operations
    case ViewData = 'lihat_data_pengunjung';
    case ConfirmVisit = 'konfirmasi_kunjungan';
    case CreateInvitation = 'buat_undangan_tamu';

    // Criticism & Suggestions
    case ViewCriticismFeedback = 'lihat_kritik_saran_pengunjung';
    case ManageCriticismFeedback = 'kelola_kritik_saran_pengunjung';

    // Reports
    case ViewReport = 'lihat_laporan_pengunjung';

    public static function values(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }
}
