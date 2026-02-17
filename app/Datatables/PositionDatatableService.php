<?php

namespace App\Datatables;

use App\Http\Requests\DatatableRequest;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class PositionDatatableService implements DatatableServiceInterface
{
    private function getStartedQuery(DatatableRequest $request, User $loggedUser): Builder
    {
        $query = Position::query();

        if ($request->has('name') && $request->name != '') {
            $query->where('name', 'like', '%'.$request->name.'%');
        }

        if ($request->has('description') && $request->description != '') {
            $query->where('description', 'like', '%'.$request->description.'%');
        }

        if ($request->has('is_active') && $request->is_active != '') {
            $query->where('is_active', $request->is_active);
        }

        if ($request->has('users_count') && $request->users_count != '') {
            $query->has('users', '=', $request->users_count);
        }

        if ($request->has('search') && $request->search != '') {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        if ($request->has('sort_by') && $request->has('sort_direction')) {
            $query->orderBy($request->sort_by, $request->sort_direction);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query;
    }

    public function getDatatable(DatatableRequest $request, User $loggedUser): mixed
    {
        $query = $this->getStartedQuery($request, $loggedUser);
        $query->withCount(['users']);

        return $query->paginate($request->limit ?? 20)->withQueryString();
    }

    public function printExcel(DatatableRequest $request, User $loggedUser): mixed
    {
        $query = $this->getStartedQuery($request, $loggedUser);
        $query->withCount(['users']);
        $data = $query->get();

        return response()->streamDownload(function () use ($data) {
            $writer = new \OpenSpout\Writer\Xlsx\Writer;

            $options = $writer->getOptions();
            $options->setColumnWidth(40, 1);
            $options->setColumnWidth(50, 2);
            $options->setColumnWidth(15, 3);
            $options->setColumnWidth(15, 4);

            $writer->openToFile('php://output');

            $headerRow = \OpenSpout\Common\Entity\Row::fromValues([
                'Nama',
                'Deskripsi',
                'Jumlah Pegawai',
                'Status',
            ]);
            $writer->addRow($headerRow);

            foreach ($data as $item) {
                $row = \OpenSpout\Common\Entity\Row::fromValues([
                    $item->name,
                    $item->description ?? '-',
                    $item->users_count,
                    $item->is_active ? 'Aktif' : 'Tidak Aktif',
                ]);
                $writer->addRow($row);
            }

            $writer->close();
        }, 'Data Jabatan Per '.date('d F Y').'.xlsx');
    }
}
