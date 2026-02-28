<?php

namespace Modules\Ticketing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\DatatableRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Ticketing\Datatables\TicketRefinementDatatableService;
use Modules\Ticketing\Services\TicketService;
use Modules\Ticketing\Enums\TicketingPermission;

class TicketRefinementController extends Controller
{
    public function __construct(
        private TicketRefinementDatatableService $datatableService,
        private TicketService $ticketService,
    ) {}

    public function index(int $id)
    {
        abort_unless(auth()->user()->can(TicketingPermission::RepairTicket->value), 403);

        $ticket = $this->ticketService->findById($id);
        abort_unless($ticket, 404);

        return Inertia::render('Ticketing/Ticket/Refinement', [
            'ticket' => [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'description' => $ticket->description,
                'status' => [
                    'value' => $ticket->status->value,
                    'label' => $ticket->status->label(),
                ],
                'asset_item' => [
                    'id' => $ticket->assetItem->id,
                    'category_name' => $ticket->assetItem->assetCategory?->name,
                    'merk' => $ticket->assetItem->merk,
                    'model' => $ticket->assetItem->model,
                    'serial_number' => $ticket->assetItem->serial_number,
                ],
                'diagnose' => $ticket->diagnose,
                'follow_up' => $ticket->follow_up,
                'note' => $ticket->note,
                'confirm_note' => $ticket->confirm_note,
                'process_note' => $ticket->process_note,
                'attachments' => $ticket->attachments,
                'process_attachments' => $ticket->process_attachments,
            ],
        ]);
    }

    public function datatable(DatatableRequest $request, int $id)
    {
        abort_unless(auth()->user()->can(TicketingPermission::RepairTicket->value), 403);

        return response()->json($this->datatableService->getDatatable($request, $id));
    }

    public function create(int $id)
    {
        abort_unless(auth()->user()->can(TicketingPermission::RepairTicket->value), 403);

        $ticket = $this->ticketService->findById($id);
        abort_unless($ticket, 404);

        return Inertia::render('Ticketing/Ticket/RefinementAdd', [
            'ticket' => [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'asset_item' => [
                    'merk' => $ticket->assetItem->merk,
                    'model' => $ticket->assetItem->model,
                ],
            ],
        ]);
    }

    public function store(Request $request, int $id)
    {
        abort_unless(auth()->user()->can(TicketingPermission::RepairTicket->value), 403);

        $request->validate([
            'date' => 'required|date',
            'description' => 'required|string',
            'result' => 'required|string',
            'note' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:5120',
        ], [
            'attachments.*.max' => 'Ukuran maksimal setiap file adalah 5MB.',
        ]);

        try {
            $this->ticketService->storeRefinement($id, $request->only('date', 'description', 'result', 'note') + [
                'attachments' => $request->file('attachments') ?? [],
            ]);
            return to_route('ticketing.tickets.refinement.index', $id)->with('success', 'Data perbaikan berhasil ditambahkan.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function finish(int $id)
    {
        abort_unless(auth()->user()->can(TicketingPermission::RepairTicket->value), 403);

        try {
            $this->ticketService->finishRefinement($id);
            return to_route('ticketing.tickets.show', $id)->with('success', 'Perbaikan telah diselesaikan.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
