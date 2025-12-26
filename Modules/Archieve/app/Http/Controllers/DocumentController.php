<?php

namespace Modules\Archieve\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\DatatableRequest;
use App\Models\Division;
use App\Models\User;
use Inertia\Inertia;
use Modules\Archieve\Datatables\DocumentDatatableService;
use Modules\Archieve\DataTransferObjects\StoreDocumentDTO;
use Modules\Archieve\Http\Requests\StoreDocumentRequest;
use Modules\Archieve\Models\Document;
use Modules\Archieve\Models\CategoryContext;
use Modules\Archieve\Services\DocumentService;
use Modules\Archieve\Services\DocumentClassificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DocumentController extends Controller
{
    public function __construct(
        private DocumentService $documentService,
        private DocumentDatatableService $datatableService,
        private DocumentClassificationService $classificationService
    ) {}

    /**
     * Documents Index - determines view type by LIHAT permission priority.
     * Priority: lihat_semua_arsip > lihat_arsip_divisi > lihat_arsip_pribadi
     * 
     * Kelola permissions only affect form display (create/edit).
     */
    public function index()
    {
        $user = auth()->user();
        
        // Determine view type by LIHAT permission priority only
        if ($user->can('lihat_semua_arsip')) {
            $viewType = 'all';
        } elseif ($user->can('lihat_arsip_divisi')) {
            $viewType = 'division';
        } elseif ($user->can('lihat_arsip_pribadi')) {
            $viewType = 'personal';
        } else {
            abort(403);
        }

        return Inertia::render('Archieve/Document/Index', [
            'contexts' => $this->getContextsWithCategories(),
            'classifications' => $this->classificationService->getRoots(),
            'divisions' => Division::orderBy('name')->get(),
            'viewType' => $viewType,
            'userDivisionId' => $user->division_id,
            'userId' => $user->id,
        ]);
    }

    public function create()
    {
        $user = auth()->user();
        $canManageAll = $user->can('kelola_semua_arsip');
        
        if (!$canManageAll && !$user->can('kelola_arsip_divisi')) {
            abort(403);
        }

        return Inertia::render('Archieve/Document/Create', [
            'contexts' => $this->getContextsWithCategories(),
            'classifications' => $this->classificationService->all(),
            'divisions' => $canManageAll 
                ? Division::orderBy('name')->get() 
                : Division::where('id', $user->division_id)->get(),
            'users' => $canManageAll 
                ? User::orderBy('name')->get(['id', 'name', 'division_id'])
                : User::where('division_id', $user->division_id)->orderBy('name')->get(['id', 'name', 'division_id']),
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
        $canManageAll = $user->can('kelola_semua_arsip');
        
        if (!$canManageAll && !$user->can('kelola_arsip_divisi')) {
            abort(403);
        }

        $document->load(['classification', 'categories', 'divisions', 'users']);

        return Inertia::render('Archieve/Document/Create', [
            'document' => $document,
            'contexts' => $this->getContextsWithCategories(),
            'classifications' => $this->classificationService->all(),
            'divisions' => $canManageAll 
                ? Division::orderBy('name')->get() 
                : Division::where('id', $user->division_id)->get(),
            'users' => $canManageAll 
                ? User::orderBy('name')->get(['id', 'name', 'division_id'])
                : User::where('division_id', $user->division_id)->orderBy('name')->get(['id', 'name', 'division_id']),
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
        if (!auth()->user()->can('kelola_semua_arsip') && !auth()->user()->can('kelola_arsip_divisi')) {
            abort(403);
        }

        $this->documentService->delete($document);

        return back()->with('success', 'Dokumen berhasil dihapus.');
    }

    public function datatable(DatatableRequest $request)
    {
        $user = $request->user();

        // Determine view type based on permissions
        if ($request->has('view_type')) {
            $viewType = $request->view_type;
        } else {
            $viewType = 'all';
        }

        if ($viewType === 'personal') {
            Gate::authorize('lihat_arsip_pribadi');
            return $this->datatableService->getDatatableForUser($request, $user->id);
        }

        if ($viewType === 'division') {
            Gate::authorize('lihat_arsip_divisi');
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

        Gate::authorize('lihat_semua_arsip');
        return $this->datatableService->getDatatableAll($request, $user);
    }

    public function printExcel(DatatableRequest $request)
    {
        $user = $request->user();
        $viewType = $request->view_type ?? 'all';

        if ($viewType === 'personal') {
            Gate::authorize('lihat_arsip_pribadi');
            return $this->datatableService->printExcel($request, $user, null, $user->id);
        }

        if ($viewType === 'division') {
            Gate::authorize('lihat_arsip_divisi');
            if (!$user->division_id) {
                abort(400, 'User tidak memiliki divisi.');
            }
            return $this->datatableService->printExcel($request, $user, $user->division_id, null);
        }

        Gate::authorize('lihat_semua_arsip');
        return $this->datatableService->printExcel($request, $user);
    }

    /**
     * Get classification children for cascading dropdown.
     */
    public function getClassificationChildren(int $parentId)
    {
        return $this->classificationService->find($parentId)->children;
    }

    /**
     * Get users by division for personal archive selection.
     */
    public function getUsersByDivision(int $divisionId)
    {
        return User::where('division_id', $divisionId)->orderBy('name')->get(['id', 'name']);
    }

    private function getContextsWithCategories()
    {
        return CategoryContext::with(['categories' => function ($q) {
            $q->orderBy('name');
        }])->orderBy('name')->get();
    }

    /**
     * Document Search page - requires pencarian_dokumen permission.
     */
    public function search(Request $request)
    {
        $user = auth()->user();
        if (!$user->can('pencarian_dokumen_keseluruhan') && 
            !$user->can('pencarian_dokumen_divisi') && 
            !$user->can('pencarian_dokumen_pribadi')) {
            abort(403);
        }

        // Get initially filtered classifications (filtered by permission scope)
        $classifications = $this->getFilteredClassificationsTree(new Request());
        $contexts = $this->getContextsWithCategories();
        $divisions = Division::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Archieve/Document/Search', [
            'classifications' => $classifications,
            'contexts' => $contexts,
            'divisions' => $divisions,
        ]);
    }

    public function searchResults(DatatableRequest $request)
    {
        $user = auth()->user();
        if (!$user->can('pencarian_dokumen_keseluruhan') && 
            !$user->can('pencarian_dokumen_divisi') && 
            !$user->can('pencarian_dokumen_pribadi')) {
            abort(403);
        }

        $query = Document::with(['classification', 'categories', 'divisions', 'users', 'uploader'])
            ->orderBy('created_at', 'desc');

        $this->applySearchScope($query, $user);

        // Filter by exact classification
        if ($request->filled('classification_id')) {
            $query->where('classification_id', $request->classification_id);
        }

        // Filter by categories (multi)
        if ($request->filled('category_ids') && is_array($request->category_ids)) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->whereIn('archieve_categories.id', $request->category_ids);
            });
        }

        // Filter by divisions (multi)
        if ($request->filled('division_ids') && is_array($request->division_ids)) {
            $query->whereHas('divisions', function ($q) use ($request) {
                $q->whereIn('divisions.id', $request->division_ids);
            });
        }

        // Search by user name
        if ($request->filled('user_name')) {
            $query->whereHas('users', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->user_name . '%');
            });
        }

        // Search by title
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $documents = $query->paginate($request->per_page ?? 15);

        return response()->json($documents);
    }


    /**
     * Get filtered classifications tree based on document filters.
     */
    public function filteredClassifications(DatatableRequest $request)
    {
        return response()->json($this->getFilteredClassificationsTree($request));
    }

    /**
     * Shared logic to get filtered classification tree.
     */
    private function getFilteredClassificationsTree($request)
    {
        $user = auth()->user();
        if (!$user->can('pencarian_dokumen_keseluruhan') && 
            !$user->can('pencarian_dokumen_divisi') && 
            !$user->can('pencarian_dokumen_pribadi')) {
            return collect();
        }

        // Build base query with filters
        $query = Document::query();

        $this->applySearchScope($query, $user);

        if ($request->filled('category_ids') && is_array($request->category_ids)) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->whereIn('archieve_categories.id', $request->category_ids);
            });
        }

        if ($request->filled('division_ids') && is_array($request->division_ids)) {
            $query->whereHas('divisions', function ($q) use ($request) {
                $q->whereIn('divisions.id', $request->division_ids);
            });
        }

        if ($request->filled('user_name')) {
            $query->whereHas('users', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->user_name . '%');
            });
        }

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        // Get document counts per classification
        $counts = (clone $query)->select('classification_id', \DB::raw('count(*) as count'))
            ->groupBy('classification_id')
            ->pluck('count', 'classification_id')
            ->toArray();

        // Get filtered classifications with hierarchy
        $classifications = $this->classificationService->getAllWithHierarchy();

        // Filter to only include relevant classifications and attach counts
        return $this->filterClassificationTree($classifications, $counts);
    }

    /**
     * Get all parent classification IDs for given classification IDs.
     */
    private function getParentClassificationIds(array $classificationIds): array
    {
        $allIds = $classificationIds;

        foreach ($classificationIds as $id) {
            $classification = $this->classificationService->find($id);
            while ($classification && $classification->parent_id) {
                if (!in_array($classification->parent_id, $allIds)) {
                    $allIds[] = $classification->parent_id;
                }
                $classification = $this->classificationService->find($classification->parent_id);
            }
        }

        return $allIds;
    }

    private function filterClassificationTree($classifications, array $counts)
    {
        $filtered = collect();

        foreach ($classifications as $item) {
            // 1. First, recursively filter the children
            $filteredChildren = collect();
            if ($item->children && $item->children->isNotEmpty()) {
                $filteredChildren = $this->filterClassificationTree($item->children, $counts);
            }
            
            // 2. Set the filtered children back to the model
            $item->setRelation('children', $filteredChildren);
            
            // 3. Calculate direct and total docs
            $directCount = (int)($counts[$item->id] ?? 0);
            
            // Use a callback to sum to ensure we get our calculated property
            $childTotal = $filteredChildren->sum(function($child) {
                return (int)$child->total_documents_count;
            });
            
            $totalCount = $directCount + $childTotal;
            
            // 4. Assign counts to the model
            $item->direct_documents_count = $directCount;
            $item->total_documents_count = $totalCount;
            
            // 5. Only include this node if it actually contains documents in this branch
            if ($totalCount > 0) {
                $filtered->push($item);
            }
        }

        return $filtered;
    }

    /**
     * Check if classification has any relevant child.
     */
    private function hasRelevantChild($classification, array $relevantIds): bool
    {
        if (!$classification->children || $classification->children->isEmpty()) {
            return false;
        }

        foreach ($classification->children as $child) {
            if (in_array($child->id, $relevantIds) || $this->hasRelevantChild($child, $relevantIds)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Apply search scope based on permissions.
     */
    private function applySearchScope($query, $user)
    {
        // Enforce division scope if has 'divisi' permission but not 'keseluruhan'
        if ($user->can('pencarian_dokumen_divisi') && !$user->can('pencarian_dokumen_keseluruhan')) {
            $query->whereHas('divisions', function ($q) use ($user) {
                $q->where('divisions.id', $user->division_id);
            });
        }

        // Enforce personal scope if has 'pribadi' permission but not 'keseluruhan' or 'divisi'
        if ($user->can('pencarian_dokumen_pribadi') && 
            !$user->can('pencarian_dokumen_keseluruhan') && 
            !$user->can('pencarian_dokumen_divisi')) {
            $query->whereHas('users', function ($sq) use ($user) {
                $sq->where('users.id', $user->id);
            });
        }
    }
}
