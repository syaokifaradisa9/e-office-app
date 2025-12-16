<?php

namespace Modules\Inventory\Enums;

enum ItemTransactionType: string
{
    case In = 'In';
    case Out = 'Out';
    case Conversion = 'Conversion';
    case ConversionIn = 'Conversion In';
    case ConversionOut = 'Conversion Out';
    case StockOpname = 'Stock Opname';
    case StockOpnameLess = 'Stock Opname Kurang';
    case StockOpnameMore = 'Stock Opname Lebih';

    public function label(): string
    {
        return match ($this) {
            self::In => 'Barang Masuk',
            self::Out => 'Barang Keluar',
            self::Conversion => 'Konversi',
            self::ConversionIn => 'Konversi Masuk',
            self::ConversionOut => 'Konversi Keluar',
            self::StockOpname => 'Stock Opname',
            self::StockOpnameLess => 'Stock Opname (Kurang)',
            self::StockOpnameMore => 'Stock Opname (Lebih)',
        };
    }
}
