<?php

namespace Modules\Archieve\Enums;

enum ArchievePermission: string
{
    case ViewCategory = 'lihat_kategori_arsip';
    case ManageCategory = 'kelola_kategori_arsip';
    case ViewClassification = 'lihat_klasifikasi_arsip';
    case ManageClassification = 'kelola_klasifikasi_arsip';

    public static function values(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }
}
