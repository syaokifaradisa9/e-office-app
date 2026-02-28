<?php

namespace Modules\Ticketing\DataTransferObjects;

use Modules\Ticketing\Http\Requests\ProcessTicketRequest;

class ProcessTicketDTO
{
    public function __construct(
        public readonly string $diagnose,
        public readonly string $follow_up,
        public readonly string $processed_at,
        public readonly ?string $note,
        public readonly array $process_attachments = [],
        public readonly array $deleted_attachments = [],
        public readonly bool $needs_further_repair = false,
    ) {}

    public static function fromRequest(ProcessTicketRequest $request): self
    {
        return new self(
            diagnose: $request->validated('diagnose'),
            follow_up: $request->validated('follow_up'),
            processed_at: $request->validated('processed_at'),
            note: $request->validated('note'),
            process_attachments: $request->file('process_attachments') ?? [],
            deleted_attachments: $request->validated('deleted_attachments') ?? [],
            needs_further_repair: (bool) $request->validated('needs_further_repair', false),
        );
    }
}
