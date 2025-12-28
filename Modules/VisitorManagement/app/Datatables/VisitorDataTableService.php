<?php

namespace Modules\VisitorManagement\Datatables;

use App\Http\Requests\DatatableRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Modules\VisitorManagement\Models\Visitor;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class VisitorDataTableService
{
    public function getDatatable(DatatableRequest $request, User $loggedUser): mixed
    {
        $query = $this->getStartedQuery($request, $loggedUser);

        return $query->with(['division', 'purpose', 'confirmedBy'])
            ->paginate($request->limit ?? 20)
            ->withQueryString();
    }

    public function printExcel(DatatableRequest $request, User $loggedUser): mixed
    {
        $query = $this->getStartedQuery($request, $loggedUser);
        $data = $query->with(['division', 'purpose'])->get();

        return response()->streamDownload(function () use ($data) {
            $writer = new Writer;
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
                    ucfirst($item->status),
                ]);
                $writer->addRow($row);
            }

            $writer->close();
        }, 'Laporan Pengunjung Per '.date('d F Y').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function getStartedQuery(DatatableRequest $request, User $loggedUser): Builder
    {
        $query = Visitor::query();

        if ($request->has('visitor_name') && $request->visitor_name != '') {
            $query->where('visitor_name', 'like', '%'.$request->visitor_name.'%');
        }

        if ($request->has('organization') && $request->organization != '') {
            $query->where('organization', 'like', '%'.$request->organization.'%');
        }

        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        if ($request->has('division_id') && $request->division_id != '') {
            $query->where('division_id', $request->division_id);
        }

        if ($request->has('search') && $request->search != '') {
            $query->where(function ($q) use ($request) {
                $q->where('visitor_name', 'like', '%'.$request->search.'%')
                    ->orWhere('organization', 'like', '%'.$request->search.'%')
                    ->orWhere('phone_number', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->has('sort_by') && $request->has('sort_direction')) {
            $query->orderBy($request->sort_by, $request->sort_direction);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query;
    }
}
