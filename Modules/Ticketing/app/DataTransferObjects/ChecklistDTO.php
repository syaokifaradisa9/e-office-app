<?php

namespace Modules\Ticketing\DataTransferObjects;

use Modules\Ticketing\Http\Requests\StoreChecklistRequest;
use Modules\Ticketing\Http\Requests\UpdateChecklistRequest;

class ChecklistDTO
{
    public function __construct(
        public readonly string $label,
        public readonly ?string $description,
    ) {}

    public static function fromRequest(StoreChecklistRequest|UpdateChecklistRequest $request): self
    {
        return new self(
            label: $request->validated('label'),
            description: $request->validated('description'),
        );
    }

    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'description' => $this->description,
        ];
    }
}
