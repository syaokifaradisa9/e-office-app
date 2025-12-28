<?php

namespace Modules\VisitorManagement\Enums;

enum VisitorUserPermission: string
{
    case ViewData = 'lihat_data_pengunjung';
    case ConfirmVisit = 'konfirmasi_kunjungan';
    case ViewFeedback = 'lihat_ulasan_pengunjung';
    case ViewReport = 'lihat_laporan_pengunjung';
    case ViewDashboard = 'lihat_dashboard_pengunjung';
    case ViewMaster = 'lihat_master_manajemen_pengunjung';
    case ManageMaster = 'kelola_master_manajemen_pengunjung';
    case CreateInvitation = 'buat_undangan_tamu';
    case ViewFeedbackQuestion = 'lihat_pertanyaan_feedback';
    case ManageFeedbackQuestion = 'kelola_pertanyaan_feedback';

    public static function values(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }
}
