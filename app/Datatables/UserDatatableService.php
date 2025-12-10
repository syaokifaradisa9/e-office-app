<?php

namespace App\Datatables;

use App\Http\Requests\DatatableRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class UserDatatableService implements DatatableServiceInterface
{
    private function getStartedQuery(DatatableRequest $request, User $loggedUser): Builder
    {
        $query = User::query()->with(['division', 'position', 'roles']);

        if ($request->has('search') && $request->search != '') {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('email', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->has('division_id') && $request->division_id != '') {
            $query->where('division_id', $request->division_id);
        }

        if ($request->has('position_id') && $request->position_id != '') {
            $query->where('position_id', $request->position_id);
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

        return $query->paginate($request->limit ?? 20)->withQueryString();
    }

    public function printExcel(DatatableRequest $request, User $loggedUser): mixed
    {
        $query = $this->getStartedQuery($request, $loggedUser);
        $data = $query->get();

        return response()->streamDownload(function () use ($data) {
            $writer = new \OpenSpout\Writer\Xlsx\Writer;

            $options = $writer->getOptions();
            $options->setColumnWidth(30, 1);
            $options->setColumnWidth(35, 2);
            $options->setColumnWidth(25, 3);
            $options->setColumnWidth(25, 4);
            $options->setColumnWidth(20, 5);
            $options->setColumnWidth(15, 6);

            $writer->openToFile('php://output');

            $headerRow = \OpenSpout\Common\Entity\Row::fromValues([
                'Nama',
                'Email',
                'Divisi',
                'Jabatan',
                'Role',
                'Status',
            ]);
            $writer->addRow($headerRow);

            foreach ($data as $item) {
                $row = \OpenSpout\Common\Entity\Row::fromValues([
                    $item->name,
                    $item->email,
                    $item->division?->name ?? '-',
                    $item->position?->name ?? '-',
                    $item->roles->first()?->name ?? '-',
                    $item->is_active ? 'Aktif' : 'Tidak Aktif',
                ]);
                $writer->addRow($row);
            }

            $writer->close();
        }, 'Data Pengguna Per '.date('d F Y').'.xlsx');
    }
}
