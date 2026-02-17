<?php

namespace Modules\Archieve\Datatables;

use App\Http\Requests\DatatableRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Modules\Archieve\Models\DocumentClassification;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class DocumentClassificationDatatableService
{
    public function getDatatable(DatatableRequest $request, User $loggedUser): mixed
    {
        $query = $this->getStartedQuery($request, $loggedUser);

        return $query->with('parent')->paginate($request->limit ?? 20)->withQueryString();
    }

    public function printExcel(DatatableRequest $request, User $loggedUser): mixed
    {
        $query = $this->getStartedQuery($request, $loggedUser);
        $data = $query->with('parent')->get();

        return response()->streamDownload(function () use ($data) {
            $writer = new Writer;
            $writer->openToFile('php://output');

            $headerRow = Row::fromValues([
                'Kode',
                'Nama Klasifikasi',
                'Induk (Parent)',
                'Deskripsi',
            ]);
            $writer->addRow($headerRow);

            foreach ($data as $item) {
                $row = Row::fromValues([
                    $item->code,
                    $item->name,
                    $item->parent->name ?? '-',
                    $item->description ?? '-',
                ]);
                $writer->addRow($row);
            }

            $writer->close();
        }, 'Data Klasifikasi Arsip Per '.date('d F Y').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function getStartedQuery(DatatableRequest $request, User $loggedUser): Builder
    {
        $query = DocumentClassification::query();

        if ($request->has('code') && $request->code != '') {
            $query->where('code', 'like', '%'.$request->code.'%');
        }

        if ($request->has('name') && $request->name != '') {
            $query->where('name', 'like', '%'.$request->name.'%');
        }

        if ($request->has('parent_id') && $request->parent_id != '') {
            $query->where('parent_id', $request->parent_id);
        }

        if ($request->has('description') && $request->description != '') {
            $query->where('description', 'like', '%'.$request->description.'%');
        }

        if ($request->has('search') && $request->search != '') {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('code', 'like', '%'.$request->search.'%')
                    ->orWhere('description', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->has('sort_by') && $request->has('sort_direction')) {
            $query->orderBy($request->sort_by, $request->sort_direction);
        } else {
            $query->orderBy('code', 'asc');
        }

        return $query;
    }
}
