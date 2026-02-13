<?php

namespace Modules\Archieve\Datatables;

use App\Http\Requests\DatatableRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Modules\Archieve\Repositories\Document\DocumentRepository;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class DocumentDatatableService
{
    public function __construct(
        private DocumentRepository $repository
    ) {}

    /**
     * Get datatable for all documents (admin view).
     */
    public function getDatatableAll(DatatableRequest $request, User $loggedUser): mixed
    {
        $query = $this->repository->queryForAll();
        $query = $this->applyFilters($query, $request);

        return $query->paginate($request->limit ?? 20)->withQueryString();
    }

    /**
     * Get datatable for division documents.
     */
    public function getDatatableForDivision(DatatableRequest $request, int $divisionId): mixed
    {
        $query = $this->repository->queryForDivision($divisionId);
        $query = $this->applyFilters($query, $request);

        return $query->paginate($request->limit ?? 20)->withQueryString();
    }

    /**
     * Get datatable for personal documents.
     */
    public function getDatatableForUser(DatatableRequest $request, int $userId): mixed
    {
        $query = $this->repository->queryForUser($userId);
        $query = $this->applyFilters($query, $request);

        return $query->paginate($request->limit ?? 20)->withQueryString();
    }

    public function printExcel(DatatableRequest $request, User $loggedUser, ?int $divisionId = null, ?int $userId = null): mixed
    {
        if ($userId) {
            $query = $this->repository->queryForUser($userId);
        } elseif ($divisionId) {
            $query = $this->repository->queryForDivision($divisionId);
        } else {
            $query = $this->repository->queryForAll();
        }

        $query = $this->applyFilters($query, $request);
        $data = $query->get();

        return response()->streamDownload(function () use ($data) {
            $writer = new Writer;
            $writer->openToFile('php://output');

            $headerRow = Row::fromValues([
                'Judul',
                'Klasifikasi',
                'Kategori',
                'Divisi',
                'Ukuran File',
                'Diupload Oleh',
                'Tanggal Upload',
            ]);
            $writer->addRow($headerRow);

            foreach ($data as $item) {
                $categories = $item->categories->pluck('name')->join(', ');
                $divisions = $item->divisions->pluck('name')->join(', ');

                $row = Row::fromValues([
                    $item->title,
                    $item->classification->name ?? '-',
                    $categories ?: '-',
                    $divisions ?: '-',
                    $item->file_size_label,
                    $item->uploader->name ?? '-',
                    $item->created_at->format('d/m/Y H:i'),
                ]);
                $writer->addRow($row);
            }

            $writer->close();
        }, 'Data Arsip Dokumen Per '.date('d F Y').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function applyFilters(Builder $query, DatatableRequest $request): Builder
    {
        if ($request->has('title') && $request->title != '') {
            $query->where('title', 'like', '%'.$request->title.'%');
        }

        if ($request->has('classification_id') && $request->classification_id != '') {
            $query->where('classification_id', $request->classification_id);
        }

        if ($request->has('category') && $request->category != '') {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->category.'%');
            });
        }

        if ($request->has('division') && $request->division != '') {
            $query->whereHas('divisions', function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->division.'%');
            });
        }

        if ($request->has('uploader') && $request->uploader != '') {
            $query->whereHas('uploader', function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->uploader.'%');
            });
        }

        if ($request->has('file_size') && $request->file_size != '') {
            $query->where('file_size', 'like', '%'.$request->file_size.'%');
        }

        if ($request->has('created_at') && $request->created_at != '') {
            $date = explode('-', $request->created_at);
            if (count($date) == 2) {
                $query->whereYear('created_at', $date[0])
                    ->whereMonth('created_at', $date[1]);
            }
        }

        if ($request->has('search') && $request->search != '') {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%'.$request->search.'%')
                    ->orWhere('description', 'like', '%'.$request->search.'%')
                    ->orWhere('file_name', 'like', '%'.$request->search.'%');
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
