<?php

namespace Modules\Ticketing\DataTransferObjects;

use Modules\Ticketing\Http\Requests\StoreTicketRequest;

class StoreTicketDTO
{
    public function __construct(
        public readonly int $asset_item_id,
        public readonly string $subject,
        public readonly string $priority,
        public readonly string $priority_reason,
        public readonly string $description,
        public readonly ?string $note,
        public readonly array $attachments = [],
    ) {}

    public static function fromRequest(StoreTicketRequest $request): self
    {
        return new self(
            asset_item_id: $request->validated('asset_item_id'),
            subject: $request->validated('subject'),
            priority: $request->validated('priority'),
            priority_reason: $request->validated('priority_reason'),
            description: $request->validated('description'),
            note: $request->validated('note'),
            attachments: $request->file('attachments') ?? [],
        );
    }
}
