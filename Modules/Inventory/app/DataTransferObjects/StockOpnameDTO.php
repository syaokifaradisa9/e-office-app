<?php

namespace Modules\Inventory\DataTransferObjects;

use Illuminate\Http\Request;
use Modules\Inventory\Enums\StockOpnameStatus;

class StockOpnameDTO
{
    public function __construct(
        public readonly ?string $opname_date = null,
        public readonly ?int $division_id = null,
        public readonly ?string $notes = null,
        public readonly array $items = [],
        public readonly ?StockOpnameStatus $status = null
    ) {}

    public static function fromStoreRequest(Request $request): self
    {
        $divisionId = $request->validated('division_id');
        
        return new self(
            opname_date: $request->validated('opname_date'),
            division_id: $divisionId === 'warehouse' ? null : (int) $divisionId,
            notes: $request->validated('notes'),
            status: StockOpnameStatus::Pending
        );
    }

    /**
     * Status mapping:
     * - confirm=true → "Stock Opname" (confirmed, stock adjusted)
     * - confirm=false → "Process" (draft)
     */
    public static function fromProcessRequest(Request $request): self
    {
        return new self(
            items: $request->validated('items'),
            status: $request->boolean('confirm') ? StockOpnameStatus::StockOpname : StockOpnameStatus::Proses
        );
    }

    public static function fromFinalizeRequest(Request $request): self
    {
        return new self(
            items: $request->validated('items'),
            status: StockOpnameStatus::Finish
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'opname_date' => $this->opname_date,
            'division_id' => $this->division_id,
            'notes' => $this->notes,
            'status' => $this->status?->value,
        ], fn($value) => !is_null($value));
    }
}
