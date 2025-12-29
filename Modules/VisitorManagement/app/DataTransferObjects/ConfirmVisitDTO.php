<?php

namespace Modules\VisitorManagement\DataTransferObjects;

use Illuminate\Http\Request;

class ConfirmVisitDTO
{
    public function __construct(
        public readonly string $status,
        public readonly int $confirmed_by,
        public readonly ?string $admin_note = null,
    ) {}

    public static function fromRequest(Request $request, int $userId): self
    {
        return new self(
            status: $request->validated('status'),
            confirmed_by: $userId,
            admin_note: $request->validated('admin_note'),
        );
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'confirmed_by' => $this->confirmed_by,
            'admin_note' => $this->admin_note,
            'confirmed_at' => now(),
        ];
    }
}
