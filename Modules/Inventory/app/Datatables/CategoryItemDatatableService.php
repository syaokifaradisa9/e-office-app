<?php

namespace Modules\Inventory\Datatables;

use App\Http\Requests\DatatableRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Modules\Inventory\Models\CategoryItem;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class CategoryItemDatatableService
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

            $options = $writer->getOptions();
            $options->setColumnWidth(40, 1); // Nama Kategori
            $options->setColumnWidth(60, 2); // Deskripsi
            $options->setColumnWidth(15, 3); // Status

            $writer->openToFile('php://output');

            $headerRow = Row::fromValues([
                'Nama Kategori',
                'Deskripsi',
                'Status',
            ]);
            $writer->addRow($headerRow);

            foreach ($data as $item) {
                $row = Row::fromValues([
                    $item->name,
                    $item->description ?? '-',
                    $item->is_active ? 'Aktif' : 'Tidak Aktif',
                ]);
                $writer->addRow($row);
            }

            $writer->close();
        }, 'Data Kategori Barang Per '.date('d F Y').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function getStartedQuery(DatatableRequest $request, User $loggedUser): Builder
    {
        $query = CategoryItem::query();

        if ($request->has('name') && $request->name != '') {
            $query->where('name', 'like', '%'.$request->name.'%');
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
            $query->orderBy($request->sort_by, $request->sort_direction);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query;
    }
}
