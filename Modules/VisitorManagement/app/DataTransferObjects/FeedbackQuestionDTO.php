<?php

namespace Modules\VisitorManagement\DataTransferObjects;

use Illuminate\Http\Request;

class FeedbackQuestionDTO
{
    public function __construct(
        public readonly string $question,
        public readonly bool $is_active = true
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            question: $request->input('question'),
            is_active: $request->boolean('is_active', true)
        );
    }

    public function toArray(): array
    {
        return [
            'question' => $this->question,
            'is_active' => $this->is_active,
        ];
    }
}
