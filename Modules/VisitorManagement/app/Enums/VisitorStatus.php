<?php

namespace Modules\VisitorManagement\Enums;

enum VisitorStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Completed = 'completed';
    case Invited = 'invited';

    public function label(): string
    {
        return match($this) {
            self::Pending => 'Menunggu',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
            self::Completed => 'Selesai',
            self::Invited => 'Diundang',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Pending => 'amber',
            self::Approved => 'emerald',
            self::Rejected => 'red',
            self::Completed => 'blue',
            self::Invited => 'indigo',
        };
    }

    public static function activeStatuses(): array
    {
        return [
            self::Pending,
            self::Approved,
            self::Invited,
        ];
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
