<?php

namespace Modules\Archieve\Enums;

enum CategoryType: string
{
    case Function = 'fungsi';
    case Authenticity = 'keaslian';
    case Media = 'media';

    public function label(): string
    {
        return match ($this) {
            self::Function => 'Berdasarkan Fungsi/Kegunaan',
            self::Authenticity => 'Berdasarkan Keaslian',
            self::Media => 'Berdasarkan Bentuk/Media',
        };
    }
}
