<?php

namespace Modules\Archieve\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Archieve\Models\Category;
use Modules\Archieve\Models\CategoryContext;

class CategoryContextSeeder extends Seeder
{
    public function run(): void
    {
        $contexts = [
            [
                'name' => 'Berdasarkan Fungsi/Kegunaan',
                'description' => 'Pengelompokan arsip berdasarkan fungsi dan kegunaan dalam organisasi.',
                'categories' => [
                    [
                        'name' => 'Arsip Aktif',
                        'description' => 'Sering digunakan untuk kegiatan sehari-hari organisasi (contoh: daftar hadir, dokumen proyek).',
                    ],
                    [
                        'name' => 'Arsip Inaktif',
                        'description' => 'Jarang digunakan, frekuensi menurun, dipindahkan ke unit penyimpanan pusat.',
                    ],
                    [
                        'name' => 'Arsip Statis',
                        'description' => 'Tidak digunakan untuk administrasi harian, tapi punya nilai guna kesejarahan/kebangsaan, disimpan di Arsip Nasional (contoh: dokumen sejarah, peta kuno).',
                    ],
                ],
            ],
            [
                'name' => 'Berdasarkan Keaslian',
                'description' => 'Pengelompokan arsip berdasarkan keaslian dokumen.',
                'categories' => [
                    [
                        'name' => 'Arsip Asli',
                        'description' => 'Dokumen utama yang asli, ada tanda tangan/stempel legal.',
                    ],
                    [
                        'name' => 'Arsip Salinan',
                        'description' => 'Dibuat berbeda waktu, isinya sama dengan asli.',
                    ],
                    [
                        'name' => 'Arsip Tembusan',
                        'description' => 'Dibuat bersamaan dengan asli, untuk pihak selain penerima utama.',
                    ],
                ],
            ],
            [
                'name' => 'Berdasarkan Bentuk/Media',
                'description' => 'Pengelompokan arsip berdasarkan bentuk fisik atau media penyimpanan.',
                'categories' => [
                    [
                        'name' => 'Arsip Konvensional',
                        'description' => 'Berwujud kertas (surat, kuitansi, buku).',
                    ],
                    [
                        'name' => 'Arsip Fisik/Non-Kertas',
                        'description' => 'Peta, foto, rekaman suara, film.',
                    ],
                    [
                        'name' => 'Arsip Digital/Elektronik',
                        'description' => 'Data elektronik di flashdisk, server, format digital lainnya.',
                    ],
                    [
                        'name' => 'Arsip Mikro',
                        'description' => 'Microfilm, microfiche.',
                    ],
                ],
            ],
        ];

        foreach ($contexts as $contextData) {
            $context = CategoryContext::firstOrCreate(
                ['name' => $contextData['name']],
                ['description' => $contextData['description']]
            );

            foreach ($contextData['categories'] as $categoryData) {
                Category::firstOrCreate(
                    [
                        'name' => $categoryData['name'],
                        'context_id' => $context->id,
                    ],
                    ['description' => $categoryData['description']]
                );
            }
        }
    }
}
