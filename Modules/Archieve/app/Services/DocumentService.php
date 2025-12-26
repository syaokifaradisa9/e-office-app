<?php

namespace Modules\Archieve\Services;

use App\Models\User;
use App\Models\Division;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Archieve\DataTransferObjects\StoreDocumentDTO;
use Modules\Archieve\DataTransferObjects\SearchDocumentDTO;
use Modules\Archieve\Models\Document;
use Modules\Archieve\Repositories\Document\DocumentRepository;
use Modules\Archieve\Repositories\DivisionStorage\DivisionStorageRepository;
use App\Repositories\Division\DivisionRepository;
use App\Repositories\User\UserRepository;

class DocumentService
{
    public function __construct(
        private DocumentRepository $repository,
        private DivisionRepository $divisionRepository,
        private UserRepository $userRepository,
        private CategoryContextService $contextService,
        private DocumentClassificationService $classificationService,
        private DivisionStorageRepository $storageRepository
    ) {}

    public function store(StoreDocumentDTO $dto, UploadedFile $file, User $uploader): Document
    {
        return DB::transaction(function () use ($dto, $file, $uploader) {
            // Store the file
            $filePath = $file->store('archieve/documents', 'public');
            $fileSize = $file->getSize();

            // Create the document
            $document = $this->repository->store([
                'title' => $dto->title,
                'description' => $dto->description,
                'classification_id' => $dto->classification_id,
                'file_path' => $filePath,
                'file_name' => $file->getClientOriginalName(),
                'file_type' => $file->getClientMimeType(),
                'file_size' => $fileSize,
                'uploaded_by' => $uploader->id,
            ]);

            // Sync categories
            $this->repository->syncCategories($document, $dto->category_ids);

            // Sync divisions with allocated size
            $this->syncDivisionsWithStorage($document, $dto->division_ids, $fileSize);

            // Sync users (personal archive)
            if (!empty($dto->user_ids)) {
                $this->repository->syncUsers($document, $dto->user_ids);
            }

            return $document;
        });
    }

    public function update(Document $document, StoreDocumentDTO $dto, ?UploadedFile $file = null): Document
    {
        return DB::transaction(function () use ($document, $dto, $file) {
            $oldFileSize = $document->file_size;
            $oldDivisionIds = $document->divisions->pluck('id')->toArray();
            $fileSize = $oldFileSize;

            // Update file if provided
            if ($file) {
                // Delete old file
                Storage::disk('public')->delete($document->file_path);

                // Store new file
                $filePath = $file->store('archieve/documents', 'public');
                $fileSize = $file->getSize();

                $document = $this->repository->update($document, [
                    'title' => $dto->title,
                    'description' => $dto->description,
                    'classification_id' => $dto->classification_id,
                    'file_path' => $filePath,
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $fileSize,
                ]);
            } else {
                $document = $this->repository->update($document, [
                    'title' => $dto->title,
                    'description' => $dto->description,
                    'classification_id' => $dto->classification_id,
                ]);
            }

            // Sync categories
            $this->repository->syncCategories($document, $dto->category_ids);

            // Recalculate division storage (release old, allocate new)
            $this->recalculateDivisionStorage($document, $oldDivisionIds, $oldFileSize, $dto->division_ids, $fileSize);

            // Sync users
            $this->repository->syncUsers($document, $dto->user_ids ?? []);

            return $document->fresh(['classification', 'categories', 'divisions', 'users']);
        });
    }

    public function delete(Document $document): bool
    {
        return DB::transaction(function () use ($document) {
            // Release storage from divisions
            $this->releaseDivisionStorage($document);

            // Delete file
            Storage::disk('public')->delete($document->file_path);

            return $this->repository->delete($document);
        });
    }

    public function find(int $id): Document
    {
        return $this->repository->find($id);
    }

    public function getFormDivisions(User $user): Collection
    {
        if ($user->can('kelola_semua_arsip')) {
            return $this->divisionRepository->all();
        }
        
        $division = $this->divisionRepository->find($user->division_id);
        return $division ? collect([$division]) : collect();
    }

    public function getFormUsers(User $user): Collection
    {
        if ($user->can('kelola_semua_arsip')) {
            return $this->userRepository->all();
        }
        
        return $this->userRepository->getByDivisionWithColumns($user->division_id);
    }

    public function getContextsWithCategories(): Collection
    {
        return $this->contextService->allWithCategories();
    }

    public function getUsersByDivision(int $divisionId): Collection
    {
        return $this->userRepository->getByDivisionWithColumns($divisionId, ['id', 'name']);
    }

    public function searchDocuments(SearchDocumentDTO $dto, User $user): LengthAwarePaginator
    {
        $query = $this->repository->searchQuery();
        
        $this->applySearchScope($query, $user);

        // Filter by exact classification
        if ($dto->classification_id) {
            $query->where('classification_id', $dto->classification_id);
        }

        // Filter by categories
        if (!empty($dto->category_ids)) {
            $query->whereHas('categories', function ($q) use ($dto) {
                $q->whereIn('archieve_categories.id', $dto->category_ids);
            });
        }

        // Filter by divisions
        if (!empty($dto->division_ids)) {
            $query->whereHas('divisions', function ($q) use ($dto) {
                $q->whereIn('divisions.id', $dto->division_ids);
            });
        }

        // Search by user name
        if ($dto->user_name) {
            $query->whereHas('users', function ($q) use ($dto) {
                $q->where('name', 'like', '%' . $dto->user_name . '%');
            });
        }

        // Search by title
        if ($dto->search) {
            $query->where('title', 'like', '%' . $dto->search . '%');
        }

        return $query->orderBy('created_at', 'desc')->paginate($dto->per_page);
    }

    public function getFilteredClassificationsTree(SearchDocumentDTO $dto, User $user): Collection
    {
        // Build base query with filters
        $query = $this->repository->searchQuery();
        $this->applySearchScope($query, $user);

        if (!empty($dto->category_ids)) {
            $query->whereHas('categories', function ($q) use ($dto) {
                $q->whereIn('archieve_categories.id', $dto->category_ids);
            });
        }

        if (!empty($dto->division_ids)) {
            $query->whereHas('divisions', function ($q) use ($dto) {
                $q->whereIn('divisions.id', $dto->division_ids);
            });
        }

        if ($dto->user_name) {
            $query->whereHas('users', function ($q) use ($dto) {
                $q->where('name', 'like', '%' . $dto->user_name . '%');
            });
        }

        if ($dto->search) {
            $query->where('title', 'like', '%' . $dto->search . '%');
        }

        // Get document counts per classification
        $counts = $this->repository->getClassificationDocumentCounts($query);

        // Get filtered classifications with hierarchy
        $classifications = $this->classificationService->getAllWithHierarchy();

        // Filter to only include relevant classifications and attach counts
        return $this->filterClassificationTree($classifications, $counts);
    }

    private function applySearchScope($query, User $user): void
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

    private function filterClassificationTree(Collection $classifications, array $counts): Collection
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
     * Sync divisions and allocate storage evenly.
     */
    private function syncDivisionsWithStorage(Document $document, array $divisionIds, int $fileSize): void
    {
        if (empty($divisionIds)) {
            return;
        }

        $divisionCount = count($divisionIds);
        $allocatedSizePerDivision = (int) floor($fileSize / $divisionCount);

        $divisionData = [];
        foreach ($divisionIds as $divisionId) {
            $divisionData[$divisionId] = ['allocated_size' => $allocatedSizePerDivision];

            // Update division storage used_size using repository
            $this->storageRepository->incrementUsedSize($divisionId, $allocatedSizePerDivision);
        }

        $this->repository->syncDivisions($document, $divisionData);
    }

    /**
     * Recalculate storage when divisions or file size changes.
     */
    private function recalculateDivisionStorage(
        Document $document,
        array $oldDivisionIds,
        int $oldFileSize,
        array $newDivisionIds,
        int $newFileSize
    ): void {
        // Release storage from old divisions
        if (!empty($oldDivisionIds)) {
            $oldAllocatedSize = (int) floor($oldFileSize / count($oldDivisionIds));
            foreach ($oldDivisionIds as $divisionId) {
                $this->storageRepository->decrementUsedSize($divisionId, $oldAllocatedSize);
            }
        }

        // Allocate to new divisions
        $this->syncDivisionsWithStorage($document, $newDivisionIds, $newFileSize);
    }

    /**
     * Release storage when document is deleted.
     */
    private function releaseDivisionStorage(Document $document): void
    {
        foreach ($document->divisions as $division) {
            $allocatedSize = $division->pivot->allocated_size;
            $this->storageRepository->decrementUsedSize($division->id, $allocatedSize);
        }
    }
}
