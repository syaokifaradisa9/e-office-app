<?php

namespace Modules\Archieve\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Archieve\Models\Category;
use Modules\Archieve\Enums\CategoryType;

class ArchieveCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $contexts = [
            'Berdasarkan Fungsi/Kegunaan' => [
                'Arsip Aktif' => 'Sering digunakan untuk kegiatan sehari-hari organisasi (contoh: daftar hadir, dokumen proyek).',
                'Arsip Inaktif' => 'Jarang digunakan, frekuensi menurun, dipindahkan ke unit penyimpanan pusat.',
                'Arsip Statis' => 'Tidak digunakan untuk administrasi harian, tapi punya nilai guna kesejarahan/kebangsaan.',
            ],
            'Berdasarkan Keaslian' => [
                'Arsip Asli' => 'Dokumen utama yang asli, ada tanda tangan/stempel legal.',
                'Arsip Salinan' => 'Dibuat berbeda waktu, isinya sama dengan asli.',
                'Arsip Tembusan' => 'Dibuat bersamaan dengan asli, untuk pihak selain penerima utama.',
            ],
            'Berdasarkan Bentuk/Media' => [
                'Arsip Konvensional' => 'Berwujud kertas (surat, kuitansi, buku).',
                'Arsip Fisik/Non-Kertas' => 'Peta, foto, rekaman suara, film.',
                'Arsip Digital/Elektronik' => 'Data elektronik di flashdisk, server, format digital lainnya.',
                'Arsip Mikro' => 'Microfilm, microfiche.',
            ],
        ];

        foreach ($contexts as $contextName => $categories) {
            $context = \Modules\Archieve\Models\CategoryContext::firstOrCreate(
                ['name' => $contextName]
            );

            foreach ($categories as $catName => $description) {
                Category::updateOrCreate(
                    ['name' => $catName],
                    [
                        'context_id' => $context->id,
                        'description' => $description
                    ]
                );
            }
        }
    }
}
