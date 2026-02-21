<?php

namespace Modules\Ticketing\DataTransferObjects;

use Modules\Ticketing\Http\Requests\MaintenanceChecklistRequest;

class MaintenanceChecklistDTO
{
    public function __construct(
        public readonly string $actual_date,
        public readonly ?string $note,
        public readonly array $checklists,
        public readonly array $attachments = [],
        public readonly bool $needs_further_repair = false,
    ) {}

    public static function fromRequest(MaintenanceChecklistRequest $request): self
    {
        return new self(
            actual_date: $request->validated('actual_date'),
            note: $request->validated('note'),
            checklists: $request->validated('checklists'),
            attachments: $request->file('attachments') ?? [],
            needs_further_repair: (bool) $request->validated('needs_further_repair', false),
        );
    }

    public function toArray(): array
    {
        return [
            'actual_date' => $this->actual_date,
            'note' => $this->note,
            'checklist_results' => $this->checklists,
            'attachments' => $this->attachments,
            'needs_further_repair' => $this->needs_further_repair,
        ];
    }
}
