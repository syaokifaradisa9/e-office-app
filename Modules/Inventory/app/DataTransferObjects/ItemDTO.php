<?php

namespace Modules\Inventory\DataTransferObjects;

use Illuminate\Http\Request;

class ItemDTO
{
    public function __construct(
        public readonly string $name,
        public readonly ?int $category_id,
        public readonly string $unit_of_measure,
        public readonly int $stock,
        public readonly ?string $description,
        public readonly int $multiplier,
        public readonly ?int $reference_item_id,
        public readonly ?int $division_id = null
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->validated('name'),
            category_id: $request->validated('category_id'),
            unit_of_measure: $request->validated('unit_of_measure'),
            stock: $request->validated('stock'),
            description: $request->validated('description'),
            multiplier: $request->validated('multiplier') ?? 1,
            reference_item_id: $request->validated('reference_item_id'),
            division_id: $request->validated('division_id')
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'category_id' => $this->category_id,
            'unit_of_measure' => $this->unit_of_measure,
            'stock' => $this->stock,
            'description' => $this->description,
            'multiplier' => $this->multiplier,
            'reference_item_id' => $this->reference_item_id,
            'division_id' => $this->division_id,
        ];
    }
}
