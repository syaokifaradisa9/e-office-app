<?php

namespace Modules\Archieve\Datatables;

use App\Http\Requests\DatatableRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Modules\Archieve\Models\CategoryContext;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class CategoryContextDatatableService
{
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
            $writer = new Writer;
            $writer->openToFile('php://output');

            $headerRow = Row::fromValues([
                'Nama Konteks',
                'Deskripsi',
            ]);
            $writer->addRow($headerRow);

            foreach ($data as $item) {
                $row = Row::fromValues([
                    $item->name,
                    $item->description ?? '-',
                ]);
                $writer->addRow($row);
            }

            $writer->close();
        }, 'Data Konteks Arsip Per '.date('d F Y').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function getStartedQuery(DatatableRequest $request, User $loggedUser): Builder
    {
        $query = CategoryContext::query();

        if ($request->has('name') && $request->name != '') {
            $query->where('name', 'like', '%'.$request->name.'%');
        }

        if ($request->has('search') && $request->search != '') {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('description', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->has('sort_by') && $request->has('sort_direction')) {
            $query->orderBy($request->sort_by, $request->sort_direction);
        } else {
            $query->orderBy('name', 'asc');
        }

        return $query;
    }
}
