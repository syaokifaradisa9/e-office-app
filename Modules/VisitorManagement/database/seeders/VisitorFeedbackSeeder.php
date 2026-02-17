<?php

namespace Modules\VisitorManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\VisitorManagement\Models\Visitor;
use Modules\VisitorManagement\Models\VisitorFeedback;
use Modules\VisitorManagement\Models\VisitorFeedbackRating;
use Modules\VisitorManagement\Models\VisitorFeedbackQuestion;
use Modules\VisitorManagement\Enums\VisitorStatus;

class VisitorFeedbackSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $completedVisitors = Visitor::where('status', VisitorStatus::Completed)->get();
        $questions = VisitorFeedbackQuestion::where('is_active', true)->get();

        if ($questions->isEmpty()) {
            return;
        }

        $feedbackNotes = [
            'Pelayanan sangat memuaskan, petugas ramah dan membantu.',
            'Proses konfirmasi cukup cepat, ruang tunggu nyaman.',
            'Sangat baik, terima kasih atas fasilitas air minum di ruang tunggu.',
            'Petugas sangat informatif menjelaskan alur kunjungan.',
            'Sistem antrian sudah bagus dan teratur.',
            'Mungkin bisa ditambah kursi di ruang tunggu saat ramai.',
            'Kebersihan lobby sangat terjaga, mantap.',
            'Pelayanan oke, internet di ruang tunggu lancar.',
            'Terima kasih, kunjungan saya berjalan lancar tanpa kendala.',
            'Aplikasi pengisian datanya user-friendly dan cepat.',
        ];

        foreach ($completedVisitors as $visitor) {
            // Give feedback to 70% of completed visitors
            if (rand(1, 100) <= 70) {
                $feedback = VisitorFeedback::create([
                    'visitor_id' => $visitor->id,
                    'feedback_note' => collect($feedbackNotes)->random(),
                    'is_read' => rand(0, 1),
                    'created_at' => $visitor->check_out_at ? $visitor->check_out_at->addMinutes(rand(5, 30)) : now(),
                ]);

                foreach ($questions as $question) {
                    VisitorFeedbackRating::create([
                        'visitor_feedback_id' => $feedback->id,
                        'question_id' => $question->id,
                        'rating' => rand(4, 5), // Most mock feedback is positive
                    ]);
                }
            }
        }
    }
}
