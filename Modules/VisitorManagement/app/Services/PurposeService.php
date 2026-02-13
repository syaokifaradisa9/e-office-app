<?php

namespace Modules\VisitorManagement\Services;

use Modules\VisitorManagement\Repositories\Purpose\PurposeRepository;
use Illuminate\Database\Eloquent\Collection;

use Modules\VisitorManagement\DataTransferObjects\PurposeDTO;
use Modules\VisitorManagement\Models\VisitorPurpose;

class PurposeService
{
    public function __construct(
        private PurposeRepository $purposeRepository
    ) {}

    public function getActivePurposes(): Collection
    {
        return $this->purposeRepository->all()->where('is_active', true)->values();
    }

    public function getAllPurposes(): Collection
    {
        return $this->purposeRepository->all();
    }

    public function store(PurposeDTO $dto): VisitorPurpose
    {
        return $this->purposeRepository->create($dto->toArray());
    }

    public function update(VisitorPurpose $purpose, PurposeDTO $dto): bool
    {
        return $this->purposeRepository->update($purpose, $dto->toArray());
    }

    public function delete(VisitorPurpose $purpose): bool
    {
        return $this->purposeRepository->delete($purpose);
    }

    public function hasVisitors(VisitorPurpose $purpose): bool
    {
        return $this->purposeRepository->hasVisitors($purpose);
    }

    public function toggleStatus(VisitorPurpose $purpose): bool
    {
        return $this->purposeRepository->update($purpose, [
            'is_active' => !$purpose->is_active
        ]);
    }

    public function exportExcel()
    {
        $data = $this->purposeRepository->getDatatableQuery([])->get();

        return response()->streamDownload(function () use ($data) {
            $writer = new \OpenSpout\Writer\XLSX\Writer();
            $writer->openToFile('php://output');

            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
                'Nama Keperluan',
                'Deskripsi',
                'Status'
            ]));

            foreach ($data as $item) {
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
                    $item->name,
                    $item->description ?? '-',
                    $item->is_active ? 'Aktif' : 'Tidak Aktif'
                ]));
            }

            $writer->close();
        }, 'Master_Keperluan_Kunjungan_' . date('Ymd_His') . '.xlsx');
    }
}
