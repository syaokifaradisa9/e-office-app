<?php

namespace Modules\Archieve\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Archieve\Models\DocumentClassification;

class DocumentClassificationSeeder extends Seeder
{
    public function run(): void
    {
        $classifications = [
            [
                'code' => 'UM',
                'name' => 'Umum',
                'description' => 'Dokumen umum dan korespondensi',
                'children' => [
                    ['code' => 'UM.01', 'name' => 'Surat Masuk', 'description' => 'Surat yang diterima dari pihak eksternal'],
                    ['code' => 'UM.02', 'name' => 'Surat Keluar', 'description' => 'Surat yang dikirim ke pihak eksternal'],
                    ['code' => 'UM.03', 'name' => 'Nota Dinas', 'description' => 'Surat internal antar unit kerja'],
                    ['code' => 'UM.04', 'name' => 'Pengumuman', 'description' => 'Pengumuman resmi instansi'],
                ],
            ],
            [
                'code' => 'KP',
                'name' => 'Kepegawaian',
                'description' => 'Dokumen terkait kepegawaian dan SDM',
                'children' => [
                    ['code' => 'KP.01', 'name' => 'Data Pegawai', 'description' => 'Data dan biodata pegawai'],
                    ['code' => 'KP.02', 'name' => 'SK Pengangkatan', 'description' => 'Surat Keputusan pengangkatan pegawai'],
                    ['code' => 'KP.03', 'name' => 'SK Mutasi', 'description' => 'Surat Keputusan mutasi/perpindahan'],
                    ['code' => 'KP.04', 'name' => 'Cuti', 'description' => 'Dokumen permohonan dan persetujuan cuti'],
                    ['code' => 'KP.05', 'name' => 'Kenaikan Pangkat', 'description' => 'Dokumen kenaikan pangkat/golongan'],
                ],
            ],
            [
                'code' => 'KU',
                'name' => 'Keuangan',
                'description' => 'Dokumen terkait keuangan dan anggaran',
                'children' => [
                    ['code' => 'KU.01', 'name' => 'Anggaran', 'description' => 'Dokumen perencanaan anggaran'],
                    ['code' => 'KU.02', 'name' => 'Laporan Keuangan', 'description' => 'Laporan keuangan periodik'],
                    ['code' => 'KU.03', 'name' => 'SPJ', 'description' => 'Surat Pertanggungjawaban keuangan'],
                    ['code' => 'KU.04', 'name' => 'Pajak', 'description' => 'Dokumen perpajakan'],
                ],
            ],
            [
                'code' => 'PL',
                'name' => 'Pelayanan',
                'description' => 'Dokumen terkait pelayanan publik',
                'children' => [
                    ['code' => 'PL.01', 'name' => 'Permohonan Layanan', 'description' => 'Dokumen permohonan layanan dari masyarakat'],
                    ['code' => 'PL.02', 'name' => 'Perizinan', 'description' => 'Dokumen perizinan dan sertifikasi'],
                    ['code' => 'PL.03', 'name' => 'Pengaduan', 'description' => 'Dokumen pengaduan masyarakat'],
                ],
            ],
            [
                'code' => 'PR',
                'name' => 'Perencanaan',
                'description' => 'Dokumen perencanaan dan program',
                'children' => [
                    ['code' => 'PR.01', 'name' => 'Rencana Kerja', 'description' => 'Dokumen rencana kerja tahunan'],
                    ['code' => 'PR.02', 'name' => 'Laporan Kinerja', 'description' => 'Laporan kinerja dan evaluasi'],
                    ['code' => 'PR.03', 'name' => 'Notulensi Rapat', 'description' => 'Hasil rapat dan notulensi'],
                ],
            ],
            [
                'code' => 'AS',
                'name' => 'Aset',
                'description' => 'Dokumen pengelolaan aset dan inventaris',
                'children' => [
                    ['code' => 'AS.01', 'name' => 'Pengadaan Barang', 'description' => 'Dokumen pengadaan barang dan jasa'],
                    ['code' => 'AS.02', 'name' => 'Inventaris', 'description' => 'Daftar inventaris dan aset'],
                    ['code' => 'AS.03', 'name' => 'Pemeliharaan', 'description' => 'Dokumen pemeliharaan aset'],
                    ['code' => 'AS.04', 'name' => 'Penghapusan', 'description' => 'Dokumen penghapusan aset'],
                ],
            ],
        ];

        foreach ($classifications as $parentData) {
            $parent = DocumentClassification::firstOrCreate(
                ['code' => $parentData['code']],
                [
                    'name' => $parentData['name'],
                    'description' => $parentData['description'],
                    'parent_id' => null,
                ]
            );

            if (isset($parentData['children'])) {
                foreach ($parentData['children'] as $childData) {
                    DocumentClassification::firstOrCreate(
                        ['code' => $childData['code']],
                        [
                            'name' => $childData['name'],
                            'description' => $childData['description'],
                            'parent_id' => $parent->id,
                        ]
                    );
                }
            }
        }
    }
}
