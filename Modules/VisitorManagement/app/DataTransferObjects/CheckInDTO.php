<?php

namespace Modules\VisitorManagement\DataTransferObjects;

use Illuminate\Http\Request;

class CheckInDTO
{
    public function __construct(
        public readonly string $visitor_name,
        public readonly string $phone_number,
        public readonly string $organization,
        public readonly int $division_id,
        public readonly int $purpose_id,
        public readonly string $purpose_detail,
        public readonly int $visitor_count,
        public readonly ?string $photo_url = null,
        public readonly ?int $invited_id = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            visitor_name: $request->validated('visitor_name'),
            phone_number: $request->validated('phone_number'),
            organization: $request->validated('organization'),
            division_id: (int) $request->validated('division_id'),
            purpose_id: (int) $request->validated('purpose_id'),
            purpose_detail: $request->validated('purpose_detail'),
            visitor_count: (int) ($request->validated('visitor_count') ?? 1),
            photo_url: $request->validated('photo_url'),
            invited_id: $request->validated('invited_id'),
        );
    }

    public function toArray(): array
    {
        return [
            'visitor_name' => $this->visitor_name,
            'phone_number' => $this->phone_number,
            'organization' => $this->organization,
            'division_id' => $this->division_id,
            'purpose_id' => $this->purpose_id,
            'purpose_detail' => $this->purpose_detail,
            'visitor_count' => $this->visitor_count,
            'photo_url' => $this->photo_url,
            'invited_id' => $this->invited_id,
            'status' => 'pending',
        ];
    }
}
