<?php

namespace Modules\Archieve\Datatables;

use App\Http\Requests\DatatableRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Modules\Archieve\Models\Category;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class CategoryDatatableService
{
    public function getDatatable(DatatableRequest $request, User $loggedUser): mixed
    {
        $query = $this->getStartedQuery($request, $loggedUser);

        return $query->with('context')->paginate($request->limit ?? 20)->withQueryString();
    }

    public function printExcel(DatatableRequest $request, User $loggedUser): mixed
    {
        $query = $this->getStartedQuery($request, $loggedUser);
        $data = $query->with('context')->get();

        return response()->streamDownload(function () use ($data) {
            $writer = new Writer;
            $writer->openToFile('php://output');

            $headerRow = Row::fromValues([
                'Nama Kategori',
                'Konteks',
                'Deskripsi',
            ]);
            $writer->addRow($headerRow);

            foreach ($data as $item) {
                $row = Row::fromValues([
                    $item->name,
                    $item->context->name ?? '-',
                    $item->description ?? '-',
                ]);
                $writer->addRow($row);
            }

            $writer->close();
        }, 'Data Kategori Arsip Per '.date('d F Y').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function getStartedQuery(DatatableRequest $request, User $loggedUser): Builder
    {
        $query = Category::query();

        if ($request->has('name') && $request->name != '') {
            $query->where('name', 'like', '%'.$request->name.'%');
        }

        if ($request->has('context_id') && $request->context_id != '') {
            $query->where('context_id', $request->context_id);
        }

        if ($request->has('description') && $request->description != '') {
            $query->where('description', 'like', '%'.$request->description.'%');
        }

        if ($request->has('search') && $request->search != '') {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('description', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->has('sort_by') && $request->has('sort_direction')) {
            if ($request->sort_by === 'type') {
                 $query->join('archieve_category_contexts', 'archieve_categories.context_id', '=', 'archieve_category_contexts.id')
                       ->orderBy('archieve_category_contexts.name', $request->sort_direction)
                       ->select('archieve_categories.*');
            } else {
                $query->orderBy($request->sort_by, $request->sort_direction);
            }
        } else {
            $query->orderBy('context_id', 'asc')->orderBy('name', 'asc');
        }

        return $query;
    }
}
