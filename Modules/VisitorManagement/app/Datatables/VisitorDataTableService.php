<?php

namespace Modules\VisitorManagement\Datatables;

use App\Http\Requests\DatatableRequest;
use App\Models\User;
use Modules\VisitorManagement\Repositories\Visitor\VisitorRepository;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class VisitorDataTableService
{
    public function __construct(
        private VisitorRepository $visitorRepository
    ) {}

    public function getDatatable(DatatableRequest $request, User $loggedUser): mixed
    {
        return $this->visitorRepository->getDatatableQuery($request->all())
            ->with(['division', 'purpose', 'confirmedBy'])
            ->paginate($request->limit ?? 20)
            ->withQueryString();
    }

    public function printExcel(DatatableRequest $request, User $loggedUser): mixed
    {
        $data = $this->visitorRepository->getDatatableQuery($request->all())
            ->with(['division', 'purpose'])
            ->get();

        return response()->streamDownload(function () use ($data) {
            $writer = new Writer();
            $writer->openToFile('php://output');

            $headerRow = Row::fromValues([
                'Nama Tamu',
                'Instansi',
                'No. Telepon',
                'Tujuan Divisi',
                'Keperluan',
                'Detail Keperluan',
                'Jumlah Tamu',
                'Waktu Masuk',
                'Waktu Keluar',
                'Status',
            ]);
            $writer->addRow($headerRow);

            foreach ($data as $item) {
                $row = Row::fromValues([
                    $item->visitor_name,
                    $item->organization,
                    $item->phone_number,
                    $item->division->name ?? '-',
                    $item->purpose->name ?? '-',
                    $item->purpose_detail,
                    $item->visitor_count,
                    $item->check_in_at->format('d/m/Y H:i'),
                    $item->check_out_at ? $item->check_out_at->format('d/m/Y H:i') : '-',
                    $item->status->label(),
                ]);
                $writer->addRow($row);
            }

            $writer->close();
        }, 'Laporan Pengunjung Per '.date('d F Y').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
