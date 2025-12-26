<?php

namespace Modules\Archieve\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\DatatableRequest;
use Inertia\Inertia;
use Modules\Archieve\Datatables\DocumentDatatableService;
use Modules\Archieve\DataTransferObjects\StoreDocumentDTO;
use Modules\Archieve\DataTransferObjects\SearchDocumentDTO;
use Modules\Archieve\Http\Requests\StoreDocumentRequest;
use Modules\Archieve\Http\Requests\SearchDocumentRequest;
use Modules\Archieve\Models\Document;
use Modules\Archieve\Services\DocumentService;
use Modules\Archieve\Services\DocumentClassificationService;
use Modules\Archieve\Enums\ArchieveUserPermission;
use Illuminate\Support\Facades\Gate;

class DocumentController extends Controller
{
    public function __construct(
        private DocumentService $documentService,
        private DocumentDatatableService $datatableService,
        private DocumentClassificationService $classificationService
    ) {}

    public function index()
    {
        $user = auth()->user();
        
        if ($user->can(ArchieveUserPermission::ViewAll->value)) {
            $viewType = 'all';
        } elseif ($user->can(ArchieveUserPermission::ViewDivision->value)) {
            $viewType = 'division';
        } elseif ($user->can(ArchieveUserPermission::ViewPersonal->value)) {
            $viewType = 'personal';
        } else {
            abort(403);
        }

        return Inertia::render('Archieve/Document/Index', [
            'contexts' => $this->documentService->getContextsWithCategories(),
            'classifications' => $this->classificationService->getRoots(),
            'divisions' => $this->documentService->getFormDivisions($user),
            'viewType' => $viewType,
            'userDivisionId' => $user->division_id,
            'userId' => $user->id,
        ]);
    }

    public function create()
    {
        $user = auth()->user();
        $canManageAll = $user->can(ArchieveUserPermission::ManageAll->value);
        
        if (!$canManageAll && !auth()->user()->can(ArchieveUserPermission::ManageDivision->value)) {
            abort(403);
        }

        return Inertia::render('Archieve/Document/Create', [
            'contexts' => $this->documentService->getContextsWithCategories(),
            'classifications' => $this->classificationService->all(),
            'divisions' => $this->documentService->getFormDivisions($user),
            'users' => $this->documentService->getFormUsers($user),
            'canManageAll' => $canManageAll,
            'userDivisionId' => $user->division_id,
        ]);
    }

    public function store(StoreDocumentRequest $request)
    {
        $dto = StoreDocumentDTO::fromRequest($request);
        $this->documentService->store($dto, $request->file('file'), $request->user());

        return to_route('archieve.documents.index')
            ->with('success', 'Dokumen berhasil diupload.');
    }

    public function edit(Document $document)
    {
        $user = auth()->user();
        $canManageAll = $user->can(ArchieveUserPermission::ManageAll->value);
        
        if (!$canManageAll && !auth()->user()->can(ArchieveUserPermission::ManageDivision->value)) {
            abort(403);
        }

        $document->load(['classification', 'categories', 'divisions', 'users']);

        return Inertia::render('Archieve/Document/Create', [
            'document' => $document,
            'contexts' => $this->documentService->getContextsWithCategories(),
            'classifications' => $this->classificationService->all(),
            'divisions' => $this->documentService->getFormDivisions($user),
            'users' => $this->documentService->getFormUsers($user),
            'canManageAll' => $canManageAll,
            'userDivisionId' => $user->division_id,
        ]);
    }

    public function update(StoreDocumentRequest $request, Document $document)
    {
        $dto = StoreDocumentDTO::fromRequest($request);
        $this->documentService->update($document, $dto, $request->file('file'));

        return to_route('archieve.documents.index')
            ->with('success', 'Dokumen berhasil diperbarui.');
    }

    public function destroy(Document $document)
    {
        if (!auth()->user()->can(ArchieveUserPermission::ManageAll->value) && 
            !auth()->user()->can(ArchieveUserPermission::ManageDivision->value)) {
            abort(403);
        }

        $this->documentService->delete($document);

        return back()->with('success', 'Dokumen berhasil dihapus.');
    }

    public function datatable(DatatableRequest $request)
    {
        $user = $request->user();
        $viewType = $request->view_type ?? 'all';

        if ($viewType === 'personal') {
            Gate::authorize(ArchieveUserPermission::ViewPersonal->value);
            return $this->datatableService->getDatatableForUser($request, $user->id);
        }

        if ($viewType === 'division') {
            Gate::authorize(ArchieveUserPermission::ViewDivision->value);
            if (!$user->division_id) {
                return response()->json([
                    'data' => [],
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 20,
                    'from' => 0,
                    'to' => 0,
                    'total' => 0,
                ]);
            }
            return $this->datatableService->getDatatableForDivision($request, $user->division_id);
        }

        Gate::authorize(ArchieveUserPermission::ViewAll->value);
        return $this->datatableService->getDatatableAll($request, $user);
    }

    public function printExcel(DatatableRequest $request)
    {
        $user = $request->user();
        $viewType = $request->view_type ?? 'all';

        if ($viewType === 'personal') {
            Gate::authorize(ArchieveUserPermission::ViewPersonal->value);
            return $this->datatableService->printExcel($request, $user, null, $user->id);
        }

        if ($viewType === 'division') {
            Gate::authorize(ArchieveUserPermission::ViewDivision->value);
            if (!$user->division_id) {
                abort(400, 'User tidak memiliki divisi.');
            }
            return $this->datatableService->printExcel($request, $user, $user->division_id, null);
        }

        Gate::authorize(ArchieveUserPermission::ViewAll->value);
        return $this->datatableService->printExcel($request, $user);
    }

    public function getClassificationChildren(int $parentId)
    {
        return $this->classificationService->find($parentId)->children;
    }

    public function getUsersByDivision(int $divisionId)
    {
        return $this->documentService->getUsersByDivision($divisionId);
    }

    public function search(SearchDocumentRequest $request)
    {
        $dto = SearchDocumentDTO::fromRequest($request);
        
        return Inertia::render('Archieve/Document/Search', [
            'classifications' => $this->documentService->getFilteredClassificationsTree($dto, auth()->user()),
            'contexts' => $this->documentService->getContextsWithCategories(),
            'divisions' => $this->documentService->getFormDivisions(auth()->user()),
        ]);
    }

    public function searchResults(SearchDocumentRequest $request)
    {
        $dto = SearchDocumentDTO::fromRequest($request);
        $documents = $this->documentService->searchDocuments($dto, auth()->user());

        return response()->json($documents);
    }

    public function filteredClassifications(SearchDocumentRequest $request)
    {
        $dto = SearchDocumentDTO::fromRequest($request);
        return response()->json($this->documentService->getFilteredClassificationsTree($dto, auth()->user()));
    }
}
