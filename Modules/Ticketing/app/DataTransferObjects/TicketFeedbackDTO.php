<?php

namespace Modules\Ticketing\DataTransferObjects;

use Modules\Ticketing\Http\Requests\TicketFeedbackRequest;

class TicketFeedbackDTO
{
    public function __construct(
        public readonly int $rating,
        public readonly ?string $feedback_description,
    ) {}

    public static function fromRequest(TicketFeedbackRequest $request): self
    {
        return new self(
            rating: $request->validated('rating'),
            feedback_description: $request->validated('feedback_description'),
        );
    }
}
