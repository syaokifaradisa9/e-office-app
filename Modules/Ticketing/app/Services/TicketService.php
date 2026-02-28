<?php

namespace Modules\Ticketing\Services;

use Modules\Ticketing\Models\Ticket;
use Modules\Ticketing\Repositories\Ticket\TicketRepository;
use Modules\Ticketing\Repositories\AssetItemRefinement\AssetItemRefinementRepository;
use Modules\Ticketing\DataTransferObjects\StoreTicketDTO;
use Modules\Ticketing\DataTransferObjects\ProcessTicketDTO;
use Modules\Ticketing\DataTransferObjects\TicketFeedbackDTO;
use Modules\Ticketing\Enums\TicketStatus;
use Modules\Ticketing\Enums\AssetItemStatus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class TicketService
{
    public function __construct(
        private TicketRepository $ticketRepository,
        private AssetItemRefinementRepository $refinementRepository,
    ) {}

    public function findById(int $id): ?Ticket
    {
        return $this->ticketRepository->findById($id);
    }

    public function store(StoreTicketDTO $dto): Ticket
    {
        $attachments = [];
        if (!empty($dto->attachments)) {
            foreach ($dto->attachments as $file) {
                $attachments[] = $this->uploadAndCompressImage($file, 'ticket-evidence');
            }
        }

        return $this->ticketRepository->store([
            'user_id' => auth()->id(),
            'asset_item_id' => $dto->asset_item_id,
            'subject' => $dto->subject,
            'priority' => $dto->priority,
            'priority_reason' => $dto->priority_reason,
            'description' => $dto->description,
            'note' => $dto->note,
            'attachments' => $attachments,
            'status' => TicketStatus::PENDING->value,
        ]);
    }

    public function confirm(int $id, string $action, ?string $note, ?string $realPriority, ?string $priorityReason): Ticket
    {
        $ticket = Ticket::findOrFail($id);

        if ($ticket->status !== TicketStatus::PENDING) {
            throw new \Exception('Hanya tiket dengan status Pending yang dapat dikonfirmasi.');
        }

        if ($action === 'accept') {
            $ticket->update([
                'status' => TicketStatus::PROCESS->value,
                'confirm_note' => $note,
                'real_priority' => $realPriority,
                'priority_reason' => $priorityReason,
                'confirmed_by' => auth()->id(),
                'confirmed_at' => now(),
            ]);
        } else {
            $ticket->update([
                'status' => TicketStatus::CLOSED->value,
                'confirm_note' => $note,
                'confirmed_by' => auth()->id(),
                'confirmed_at' => now(),
                'closed_at' => now(),
            ]);
        }

        return $ticket;
    }

    public function process(int $id, ProcessTicketDTO $dto): Ticket
    {
        $ticket = Ticket::findOrFail($id);

        if (!in_array($ticket->status, [TicketStatus::PROCESS, TicketStatus::FINISH, TicketStatus::REFINEMENT])) {
            throw new \Exception('Hanya tiket dengan status Proses, Selesai, atau Perbaikan yang dapat diproses.');
        }

        if ($ticket->refinements()->exists()) {
            throw new \Exception('Tiket ini sudah memiliki data perbaikan dan tidak dapat diproses ulang.');
        }

        $processAttachments = $ticket->process_attachments ?? [];

        if (!empty($dto->deleted_attachments)) {
            $processAttachments = array_filter($processAttachments, function ($attachment) use ($dto) {
                $shouldKeep = !in_array($attachment['path'], $dto->deleted_attachments);
                if (!$shouldKeep) {
                    Storage::disk('public')->delete($attachment['path']);
                }
                return $shouldKeep;
            });
            $processAttachments = array_values($processAttachments);
        }

        if (!empty($dto->process_attachments)) {
            foreach ($dto->process_attachments as $file) {
                $processAttachments[] = $this->uploadAndCompressImage($file, 'ticket-process');
            }
        }

        $newStatus = $dto->needs_further_repair
            ? TicketStatus::REFINEMENT->value
            : TicketStatus::FINISH->value;

        $ticket->update([
            'diagnose' => $dto->diagnose,
            'follow_up' => $dto->follow_up,
            'process_note' => $dto->note,
            'process_attachments' => $processAttachments,
            'status' => $newStatus,
            'processed_by' => auth()->id(),
            'processed_at' => $dto->processed_at,
            'finished_at' => $dto->processed_at, // Using same date for completion
        ]);

        if ($dto->needs_further_repair) {
            $ticket->assetItem()->update([
                'status' => AssetItemStatus::Refinement->value,
            ]);
        }

        return $ticket;
    }

    public function close(int $id): Ticket
    {
        $ticket = Ticket::findOrFail($id);

        if ($ticket->status !== TicketStatus::FINISH) {
            throw new \Exception('Hanya tiket dengan status Selesai yang dapat ditutup.');
        }

        $ticket->update([
            'status' => TicketStatus::CLOSED->value,
            'closed_at' => now(),
        ]);

        return $ticket;
    }

    public function storeRefinement(int $ticketId, array $data): void
    {
        $ticket = Ticket::findOrFail($ticketId);

        if ($ticket->status !== TicketStatus::REFINEMENT) {
            throw new \Exception('Hanya tiket dengan status Perlu Perbaikan yang dapat ditambahkan data refinement.');
        }

        $attachments = [];
        if (!empty($data['attachments'])) {
            foreach ($data['attachments'] as $file) {
                $attachments[] = $this->uploadAndCompressImage($file, 'refinement-evidence');
            }
        }

        $this->refinementRepository->store([
            'ticket_id' => $ticketId,
            'date' => $data['date'],
            'description' => $data['description'],
            'note' => $data['note'],
            'result' => $data['result'],
            'attachments' => $attachments,
        ]);
    }

    public function finishRefinement(int $ticketId): Ticket
    {
        $ticket = Ticket::findOrFail($ticketId);

        if ($ticket->status !== TicketStatus::REFINEMENT) {
            throw new \Exception('Hanya tiket dengan status Perlu Perbaikan yang dapat diselesaikan.');
        }

        $ticket->update([
            'status' => TicketStatus::FINISH->value,
            'finished_at' => now(),
        ]);

        if ($ticket->assetItem) {
            $ticket->assetItem()->update([
                'status' => \Modules\Ticketing\Enums\AssetItemStatus::Available->value,
            ]);
        }

        return $ticket;
    }

    public function storeFeedback(int $ticketId, TicketFeedbackDTO $dto): Ticket
    {
        $ticket = Ticket::findOrFail($ticketId);

        if ($ticket->status !== TicketStatus::CLOSED) {
            throw new \Exception('Hanya tiket yang sudah ditutup yang dapat diberikan feedback.');
        }

        if ($ticket->rating !== null) {
            throw new \Exception('Feedback sudah pernah diberikan untuk tiket ini.');
        }

        $ticket->update([
            'rating' => $dto->rating,
            'feedback_description' => $dto->feedback_description,
        ]);

        return $ticket;
    }

    private function uploadAndCompressImage(UploadedFile $file, string $directory): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'webp']);

        if ($isImage && extension_loaded('gd')) {
            $pathname = $file->getPathname();
            
            // Create image from source
            $src = match ($extension) {
                'jpg', 'jpeg' => @imagecreatefromjpeg($pathname),
                'png' => @imagecreatefrompng($pathname),
                'webp' => @imagecreatefromwebp($pathname),
                default => null,
            };

            if ($src) {
                $width = imagesx($src);
                $height = imagesy($src);
                $maxWidth = 1200;

                if ($width > $maxWidth) {
                    $newWidth = $maxWidth;
                    $newHeight = (int) ($height * ($maxWidth / $width));
                    
                    // PHP 8+ imagescale is efficient
                    $scaled = imagescale($src, $newWidth, $newHeight);
                    if ($scaled) {
                        imagedestroy($src);
                        $src = $scaled;
                    }
                }

                // Output to buffer
                ob_start();
                imagejpeg($src, null, 80); // quality 80%
                $imageData = ob_get_clean();
                imagedestroy($src);

                $name = uniqid() . '.jpg';
                $path = $directory . '/' . $name;
                
                Storage::disk('public')->put($path, $imageData);

                return [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'url' => Storage::disk('public')->url($path),
                    'size' => strlen($imageData),
                    'mime_type' => 'image/jpeg',
                ];
            }
        }

        // Fallback for non-images or if GD is missing
        $path = $file->store($directory, 'public');
        return [
            'name' => $file->getClientOriginalName(),
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ];
    }
}
