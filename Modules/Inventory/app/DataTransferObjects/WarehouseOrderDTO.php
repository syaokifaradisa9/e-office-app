<?php

namespace Modules\Inventory\DataTransferObjects;

use Illuminate\Http\Request;

class WarehouseOrderDTO
{
    public function __construct(
        public readonly ?string $description,
        public readonly ?string $notes,
        public readonly array $items,
        public readonly ?int $division_id = null
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            description: $request->validated('description'),
            notes: $request->validated('notes'),
            items: $request->validated('items'),
            division_id: $request->validated('division_id') ?? $request->user()->division_id
        );
    }

    public function toArray(): array
    {
        return [
            'description' => $this->description,
            'notes' => $this->notes,
            'items' => $this->items,
            'division_id' => $this->division_id,
        ];
    }
}
