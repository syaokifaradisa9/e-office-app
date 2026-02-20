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
    ) {}

    public static function fromRequest(MaintenanceChecklistRequest $request): self
    {
        return new self(
            actual_date: $request->validated('actual_date'),
            note: $request->validated('note'),
            checklists: $request->validated('checklists'),
            attachments: $request->file('attachments') ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'actual_date' => $this->actual_date,
            'note' => $this->note,
            'checklist_results' => $this->checklists,
            'attachments' => $this->attachments,
        ];
    }
}
