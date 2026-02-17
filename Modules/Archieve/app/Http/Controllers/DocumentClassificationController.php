<?php

namespace Modules\Archieve\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\DatatableRequest;
use Inertia\Inertia;
use Modules\Archieve\Datatables\DocumentClassificationDatatableService;
use Modules\Archieve\DataTransferObjects\StoreDocumentClassificationDTO;
use Modules\Archieve\Http\Requests\StoreDocumentClassificationRequest;
use Modules\Archieve\Models\DocumentClassification;
use Modules\Archieve\Services\DocumentClassificationService;
use Modules\Archieve\Enums\ArchieveUserPermission;
use Illuminate\Support\Facades\Gate;

class DocumentClassificationController extends Controller
{
    public function __construct(
        private DocumentClassificationService $classificationService,
        private DocumentClassificationDatatableService $datatableService
    ) {}

    public function index()
    {
        Gate::authorize(ArchieveUserPermission::ViewClassification->value);

        return Inertia::render('Archieve/Classification/Index', [
            'classifications' => $this->classificationService->all(),
        ]);
    }

    public function create()
    {
        Gate::authorize(ArchieveUserPermission::ManageClassification->value);

        return Inertia::render('Archieve/Classification/Create', [
            'classifications' => $this->classificationService->all(),
        ]);
    }

    public function store(StoreDocumentClassificationRequest $request)
    {
        $dto = StoreDocumentClassificationDTO::fromRequest($request);
        $this->classificationService->store($dto);

        return to_route('archieve.classifications.index')
            ->with('success', 'Klasifikasi dokumen berhasil ditambahkan.');
    }

    public function edit(DocumentClassification $classification)
    {
        Gate::authorize(ArchieveUserPermission::ManageClassification->value);

        return Inertia::render('Archieve/Classification/Create', [
            'classification' => $classification,
            'classifications' => $this->classificationService->all(),
        ]);
    }

    public function update(StoreDocumentClassificationRequest $request, DocumentClassification $classification)
    {
        $dto = StoreDocumentClassificationDTO::fromRequest($request);
        $this->classificationService->update($classification, $dto);

        return to_route('archieve.classifications.index')
            ->with('success', 'Klasifikasi dokumen berhasil diperbarui.');
    }

    public function destroy(DocumentClassification $classification)
    {
        Gate::authorize(ArchieveUserPermission::ManageClassification->value);

        $this->classificationService->delete($classification);

        return to_route('archieve.classifications.index')
            ->with('success', 'Klasifikasi dokumen berhasil dihapus.');
    }

    public function datatable(DatatableRequest $request)
    {
        Gate::authorize(ArchieveUserPermission::ViewClassification->value);

        return $this->datatableService->getDatatable($request, $request->user());
    }

    public function printExcel(DatatableRequest $request, $type)
    {
        Gate::authorize(ArchieveUserPermission::ViewClassification->value);

        return $this->datatableService->printExcel($request, $request->user());
    }
}
