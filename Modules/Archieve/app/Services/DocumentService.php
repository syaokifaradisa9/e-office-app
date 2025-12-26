<?php

namespace Modules\Archieve\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Archieve\DataTransferObjects\StoreDocumentDTO;
use Modules\Archieve\Models\Document;
use Modules\Archieve\Models\DivisionStorage;
use Modules\Archieve\Repositories\Document\DocumentRepository;

class DocumentService
{
    public function __construct(
        private DocumentRepository $repository
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

            // Update division storage used_size
            $storage = DivisionStorage::firstOrCreate(
                ['division_id' => $divisionId],
                ['max_size' => 0, 'used_size' => 0]
            );
            $storage->increment('used_size', $allocatedSizePerDivision);
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
                $storage = DivisionStorage::where('division_id', $divisionId)->first();
                if ($storage) {
                    $storage->decrement('used_size', min($oldAllocatedSize, $storage->used_size));
                }
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
            $storage = DivisionStorage::where('division_id', $division->id)->first();
            if ($storage) {
                $storage->decrement('used_size', min($allocatedSize, $storage->used_size));
            }
        }
    }

    public function find(int $id): Document
    {
        return $this->repository->find($id);
    }
}
