<?php

namespace App\Datatables;

use App\Http\Requests\DatatableRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

class RoleDatatableService implements DatatableServiceInterface
{
    private function getStartedQuery(DatatableRequest $request, User $loggedUser): Builder
    {
        $query = Role::query()->with('permissions');

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
            $options->setColumnWidth(60, 2);
            $options->setColumnWidth(15, 3);

            $writer->openToFile('php://output');

            $headerRow = \OpenSpout\Common\Entity\Row::fromValues([
                'Nama Role',
                'Permissions',
                'Jumlah Permissions',
            ]);
            $writer->addRow($headerRow);

            foreach ($data as $item) {
                $permissions = $item->permissions->pluck('name')->implode(', ');
                $row = \OpenSpout\Common\Entity\Row::fromValues([
                    $item->name,
                    $permissions ?: '-',
                    $item->permissions->count(),
                ]);
                $writer->addRow($row);
            }

            $writer->close();
        }, 'Data Role Per '.date('d F Y').'.xlsx');
    }
}
