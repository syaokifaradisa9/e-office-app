<?php

namespace Modules\Archieve\DataTransferObjects;

use Illuminate\Http\Request;

class StoreDivisionStorageDTO
{
    public function __construct(
        public readonly int $division_id,
        public readonly float $max_size_gb,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            division_id: (int) $request->validated('division_id'),
            max_size_gb: (float) $request->validated('max_size_gb'),
        );
    }

    public function toArray(): array
    {
        // Convert GB to Bytes
        $max_size_bytes = (int) ($this->max_size_gb * 1024 * 1024 * 1024);

        return [
            'division_id' => $this->division_id,
            'max_size' => $max_size_bytes,
        ];
    }
}
