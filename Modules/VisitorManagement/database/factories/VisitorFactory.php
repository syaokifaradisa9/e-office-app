<?php

namespace Modules\VisitorManagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\VisitorManagement\Models\Visitor;
use App\Models\Division;
use Modules\VisitorManagement\Models\VisitorPurpose;
use Modules\VisitorManagement\Enums\VisitorStatus;

class VisitorFactory extends Factory
{
    protected $model = Visitor::class;

    public function definition(): array
    {
        return [
            'visitor_name' => $this->faker->name(),
            'phone_number' => $this->faker->phoneNumber(),
            'organization' => $this->faker->company(),
            'photo_url' => null,
            'division_id' => fn() => Division::inRandomOrder()->first()?->id ?? Division::factory(),
            'purpose_id' => VisitorPurpose::factory(),
            'purpose_detail' => $this->faker->randomElement([
                'Koordinasi terkait proyek sistem informasi e-office.',
                'Menghadiri rapat mingguan evaluasi kinerja divisi.',
                'Melakukan audit internal terkait penggunaan anggaran tahun berjalan.',
                'Konsultasi mengenai implementasi regulasi terbaru dari pusat.',
                'Mengantarkan berkas fisik dokumen kerjasama antar instansi.',
                'Melakukan survei lapangan untuk persiapan renovasi gedung.',
                'Bertemu dengan kepala divisi untuk membahas rencana kerja tahun depan.',
                'Melakukan instalasi dan konfigurasi perangkat keras di ruang rapat.',
                'Memberikan materi pelatihan singkat penggunaan aplikasi baru.',
                'Meninjau progress pengembangan infrastruktur jaringan di area kantor.'
            ]),
            'visitor_count' => rand(1, 5),
            'check_in_at' => now(),
            'status' => VisitorStatus::Pending,
        ];
    }
}
