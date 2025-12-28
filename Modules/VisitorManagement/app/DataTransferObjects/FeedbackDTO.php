<?php

namespace Modules\VisitorManagement\DataTransferObjects;

use Illuminate\Http\Request;

class FeedbackDTO
{
    public function __construct(
        public readonly ?string $feedback_note = null,
        public readonly array $ratings = [],
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            feedback_note: $request->validated('feedback_note'),
            ratings: $request->validated('ratings') ?? [],
        );
    }
}
