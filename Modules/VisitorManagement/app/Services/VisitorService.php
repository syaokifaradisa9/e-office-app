<?php

namespace Modules\VisitorManagement\Services;

use Modules\VisitorManagement\DataTransferObjects\CheckInDTO;
use Modules\VisitorManagement\DataTransferObjects\ConfirmVisitDTO;
use Modules\VisitorManagement\DataTransferObjects\FeedbackDTO;
use Modules\VisitorManagement\Models\Visitor;
use Modules\VisitorManagement\Repositories\Visitor\VisitorRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VisitorService
{
    public function __construct(
        private VisitorRepository $visitorRepository
    ) {}

    public function getCheckOutList(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->visitorRepository->getCheckOutList();
    }

    public function searchCheckOut(string $query): \Illuminate\Database\Eloquent\Collection
    {
        return $this->visitorRepository->searchCheckOut($query);
    }

    public function findVisitor(int $id, array $relations = []): ?Visitor
    {
        if (!empty($relations)) {
            return $this->visitorRepository->findByIdWith($id, $relations);
        }
        return $this->visitorRepository->findById($id);
    }

    public function getPublicList(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->visitorRepository->getPublicList();
    }

    public function registerVisitor(CheckInDTO $dto): Visitor
    {
        $data = $dto->toArray();
        $data['check_in_at'] = now();

        // Handle photo upload if photo_url is base64
        if (isset($data['photo_url']) && str_starts_with($data['photo_url'], 'data:image')) {
            $data['photo_url'] = $this->saveBase64Photo($data['photo_url']);
        }

        if (isset($data['invited_id']) && $data['invited_id']) {
            $visitor = $this->visitorRepository->find($data['invited_id']);
            unset($data['invited_id']);
            $visitor->update($data);
            return $visitor;
        }

        return $this->visitorRepository->create($data);
    }

    public function registerVisitorFromData(array $data): Visitor
    {
        return $this->visitorRepository->create($data);
    }

    public function updateVisitor(Visitor $visitor, CheckInDTO $dto): Visitor
    {
        $data = $dto->toArray();
        unset($data['invited_id']); // Not relevant for update

        // Handle photo upload if photo_url is base64 and different from current
        if (isset($data['photo_url']) && str_starts_with($data['photo_url'], 'data:image')) {
            $data['photo_url'] = $this->saveBase64Photo($data['photo_url']);
            
            // Delete old photo if exists/needed? Ignoring for now as we might want history or it's overwritten
        } elseif (empty($data['photo_url'])) {
            // If empty, keep existing photo (remove key from update data)
            unset($data['photo_url']);
        }

        $this->visitorRepository->update($visitor, $data);
        return $visitor->refresh();
    }

    public function confirmVisit(Visitor $visitor, ConfirmVisitDTO $dto): bool
    {
        return $this->visitorRepository->update($visitor, $dto->toArray());
    }

    public function submitFeedback(Visitor $visitor, FeedbackDTO $dto): bool
    {
        return DB::transaction(function () use ($visitor, $dto) {
            // Create overall feedback
            $feedback = $visitor->feedback()->create([
                'feedback_note' => $dto->feedback_note,
            ]);

            // Save individual ratings
            foreach ($dto->ratings as $questionId => $rating) {
                $feedback->ratings()->create([
                    'question_id' => $questionId,
                    'rating' => (int) $rating,
                ]);
            }

            // Update visitor status to completed and set check_out_at if not set
            return $this->visitorRepository->update($visitor, [
                'status' => 'completed',
                'check_out_at' => $visitor->check_out_at ?? now(),
            ]);
        });
    }

    public function checkOut(Visitor $visitor): bool
    {
        return $this->visitorRepository->update($visitor, [
            'status' => 'completed',
            'check_out_at' => now(),
        ]);
    }

    private function saveBase64Photo(string $base64Data): string
    {
        // Extract base64 content
        $replace = substr($base64Data, 0, strpos($base64Data, ',') + 1);
        $image = str_replace($replace, '', $base64Data);
        $image = str_replace(' ', '+', $image);
        
        // Generate unique filename
        $fileName = 'visitor_' . time() . '_' . Str::random(10) . '.jpg';
        $path = 'visitor/' . $fileName;

        // Save to public storage (ensure directory exists)
        Storage::disk('public')->put($path, base64_decode($image));

        return $path;
    }
}
