<?php

namespace Modules\VisitorManagement\Services;

use Modules\VisitorManagement\Repositories\FeedbackQuestion\FeedbackQuestionRepository;
use Illuminate\Database\Eloquent\Collection;

use Modules\VisitorManagement\DataTransferObjects\FeedbackQuestionDTO;
use Modules\VisitorManagement\Models\VisitorFeedbackQuestion;

class FeedbackQuestionService
{
    public function __construct(
        private FeedbackQuestionRepository $feedbackQuestionRepository
    ) {}

    public function getActiveFeedbackQuestions(): Collection
    {
        return $this->feedbackQuestionRepository->all()->where('is_active', true)->values();
    }

    public function getAllFeedbackQuestions(): Collection
    {
        return $this->feedbackQuestionRepository->all();
    }

    public function store(FeedbackQuestionDTO $dto): VisitorFeedbackQuestion
    {
        return $this->feedbackQuestionRepository->create($dto->toArray());
    }

    public function update(VisitorFeedbackQuestion $question, FeedbackQuestionDTO $dto): bool
    {
        return $this->feedbackQuestionRepository->update($question, $dto->toArray());
    }

    public function delete(VisitorFeedbackQuestion $question): bool
    {
        return $this->feedbackQuestionRepository->delete($question);
    }

    public function hasRatings(VisitorFeedbackQuestion $question): bool
    {
        return $this->feedbackQuestionRepository->hasRatings($question);
    }

    public function toggleStatus(VisitorFeedbackQuestion $question): bool
    {
        return $this->feedbackQuestionRepository->update($question, [
            'is_active' => !$question->is_active
        ]);
    }
    public function exportExcel()
    {
        $data = $this->feedbackQuestionRepository->getDatatableQuery([])->get();

        return response()->streamDownload(function () use ($data) {
            $writer = new \OpenSpout\Writer\XLSX\Writer();
            $writer->openToFile('php://output');

            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
                'Pertanyaan',
                'Status'
            ]));

            foreach ($data as $item) {
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
                    $item->question,
                    $item->is_active ? 'Aktif' : 'Tidak Aktif'
                ]));
            }

            $writer->close();
        }, 'Master_Pertanyaan_Feedback_' . date('Ymd_His') . '.xlsx');
    }
}
