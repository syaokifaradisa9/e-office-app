<?php

namespace Modules\VisitorManagement\Services;

use Illuminate\Http\Request;
use Modules\VisitorManagement\Repositories\Feedback\VisitorFeedbackRepository;

class VisitorFeedbackManagementService
{
    public function __construct(
        protected VisitorFeedbackRepository $repository
    ) {}

    public function datatable(Request $request)
    {
        $query = $this->repository->datatable();

        if ($request->has('search') && $request->search != '') {
            $query->whereHas('visitor', function($q) use ($request) {
                $q->where('visitor_name', 'like', '%' . $request->search . '%')
                  ->orWhere('organization', 'like', '%' . $request->search . '%');
            });
        }

        // Footer search: visitor_name
        if ($request->has('visitor_name') && $request->visitor_name != '') {
            $query->whereHas('visitor', function($q) use ($request) {
                $q->where('visitor_name', 'like', '%' . $request->visitor_name . '%');
            });
        }

        // Footer search: status (read/unread)
        if ($request->has('status') && $request->status != '') {
            $isRead = ($request->status === 'read' || $request->status === '1' || $request->status === 'true');
            $query->where('is_read', $isRead);
        }

        // Sort
        if ($request->has('sort_by') && $request->has('sort_direction')) {
            $sortBy = $request->sort_by;
            $direction = $request->sort_direction;
            
            if ($sortBy === 'visitor_name') {
                $query->join('visitors', 'visitor_feedbacks.visitor_id', '=', 'visitors.id')
                    ->orderBy('visitors.visitor_name', $direction)
                    ->select('visitor_feedbacks.*');
            } else {
                $query->orderBy($sortBy, $direction);
            }
        } else {
            $query->orderBy('is_read', 'asc')
                ->orderBy('created_at', 'desc');
        }

        $results = $query->paginate($request->limit ?? 10)
            ->through(function($feedback) {
                return [
                    'id' => $feedback->id,
                    'visitor_name' => $feedback->visitor->visitor_name,
                    'visit_date' => $feedback->visitor->check_in_at->locale('id')->translatedFormat('d F Y'),
                    'avg_rating' => round($feedback->ratings->avg('rating'), 1),
                    'feedback_note' => $feedback->feedback_note,
                    'is_read' => $feedback->is_read,
                    'actions' => [
                        'mark_as_read' => !$feedback->is_read
                    ]
                ];
            });

        return $results;
    }

    public function markAsRead(int $id)
    {
        return $this->repository->markAsRead($id);
    }

    public function exportExcel()
    {
        $data = $this->repository->datatable()->with(['visitor', 'ratings'])->get();

        return response()->streamDownload(function () use ($data) {
            $writer = new \OpenSpout\Writer\XLSX\Writer();
            $writer->openToFile('php://output');

            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
                'Nama Pengunjung',
                'Tanggal Kunjungan',
                'Rata-rata Rating',
                'Kritik dan Saran',
                'Status'
            ]));

            foreach ($data as $item) {
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
                    $item->visitor->visitor_name,
                    $item->visitor->check_in_at->format('d/m/Y'),
                    round($item->ratings->avg('rating'), 1),
                    $item->feedback_note,
                    $item->is_read ? 'Sudah Dibaca' : 'Belum Dibaca'
                ]));
            }

            $writer->close();
        }, 'Kritik_Saran_Pengunjung_' . date('Ymd_His') . '.xlsx');
    }
}
