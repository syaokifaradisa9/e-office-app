<?php

namespace Modules\Inventory\DataTransferObjects;

use Illuminate\Http\Request;

class StockOpnameDTO
{
    public function __construct(
        public readonly ?string $opname_date = null,
        public readonly ?int $division_id = null,
        public readonly ?string $notes = null,
        public readonly array $items = [],
        public readonly ?string $status = null
    ) {}

    public static function fromStoreRequest(Request $request): self
    {
        return new self(
            opname_date: $request->validated('opname_date'),
            division_id: $request->validated('division_id'),
            notes: $request->validated('notes'),
            status: 'Pending'
        );
    }

    public static function fromProcessRequest(Request $request): self
    {
        return new self(
            items: $request->validated('items'), // Array of ['item_id' => ..., 'physical_stock' => ..., 'notes' => ...]
            status: $request->has('confirm') ? 'Confirmed' : 'Proses'
        );
    }

    public static function fromFinalizeRequest(Request $request): self
    {
        return new self(
            items: $request->validated('items'), // Array of ['item_id' => ..., 'final_stock' => ..., 'final_notes' => ...]
            status: 'Selesai'
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'opname_date' => $this->opname_date,
            'division_id' => $this->division_id,
            'notes' => $this->notes,
            'status' => $this->status,
        ], fn($value) => !is_null($value));
    }
}
