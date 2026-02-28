<?php

namespace Modules\Ticketing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\DatatableRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Ticketing\Datatables\TicketDatatableService;
use Modules\Ticketing\Services\TicketService;
use Modules\Ticketing\Enums\TicketingPermission;
use Modules\Ticketing\Enums\TicketPriority;
use Modules\Ticketing\Enums\TicketStatus;
use Modules\Ticketing\DataTransferObjects\StoreTicketDTO;
use Modules\Ticketing\DataTransferObjects\TicketFeedbackDTO;
use Modules\Ticketing\Http\Requests\StoreTicketRequest;
use Modules\Ticketing\Http\Requests\ConfirmTicketRequest;
use Modules\Ticketing\Http\Requests\ProcessTicketRequest;
use Modules\Ticketing\Http\Requests\TicketFeedbackRequest;
use Modules\Ticketing\DataTransferObjects\ProcessTicketDTO;
use Modules\Ticketing\Models\AssetItem;

class TicketController extends Controller
{
    public function __construct(
        private TicketDatatableService $datatableService,
        private TicketService $ticketService
    ) {}

    public function index()
    {
        abort_unless(auth()->user()->canAny([
            TicketingPermission::ViewPersonalTicket->value,
            TicketingPermission::ViewDivisionTicket->value,
            TicketingPermission::ViewAllTicket->value,
        ]), 403);

        $priorities = collect(TicketPriority::cases())->map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ]);

        return Inertia::render('Ticketing/Ticket/Index', [
            'priorities' => $priorities,
        ]);
    }

    public function datatable(DatatableRequest $request)
    {
        abort_unless(auth()->user()->canAny([
            TicketingPermission::ViewPersonalTicket->value,
            TicketingPermission::ViewDivisionTicket->value,
            TicketingPermission::ViewAllTicket->value,
        ]), 403);

        $data = $this->datatableService->getDatatable($request, auth()->user());

        $data->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'subject' => $item->subject,
                'description' => $item->description,
                'status' => [
                    'value' => $item->status->value,
                    'label' => $item->status->label(),
                ],
                'priority' => $item->priority ? [
                    'value' => $item->priority->value,
                    'label' => $item->priority->label(),
                ] : null,
                'real_priority' => $item->real_priority ? [
                    'value' => $item->real_priority->value,
                    'label' => $item->real_priority->label(),
                ] : null,
                'asset_item' => [
                    'id' => $item->assetItem->id,
                    'category_name' => $item->assetItem->assetCategory?->name,
                    'merk' => $item->assetItem->merk,
                    'model' => $item->assetItem->model,
                    'serial_number' => $item->assetItem->serial_number,
                ],
                'user' => $item->user?->name,
                'user_id' => $item->user_id,
                'created_at' => $item->created_at?->format('Y-m-d H:i'),
                'rating' => $item->rating,
            ];
        });

        return response()->json($data);
    }

    public function create()
    {
        abort_unless(auth()->user()->canAny([
            TicketingPermission::ViewPersonalTicket->value,
            TicketingPermission::ViewDivisionTicket->value,
            TicketingPermission::ViewAllTicket->value,
        ]), 403);

        $user = auth()->user();
        $assetsQuery = AssetItem::with('assetCategory');

        if ($user->can(TicketingPermission::ViewAllAsset->value)) {
            // See all assets
        } elseif ($user->can(TicketingPermission::ViewDivisionAsset->value)) {
            $assetsQuery->where('division_id', $user->division_id);
        } else {
            $assetsQuery->whereHas('users', fn ($q) => $q->where('user_id', $user->id));
        }

        $assets = $assetsQuery->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'label' => "{$item->assetCategory?->name} Merek {$item->merk} Model {$item->model} SN : {$item->serial_number}",
            ])
            ->sortBy('label')
            ->values();

        $priorities = collect(TicketPriority::cases())->map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ]);

        return Inertia::render('Ticketing/Ticket/Create', [
            'assets' => $assets,
            'priorities' => $priorities,
        ]);
    }

    public function store(StoreTicketRequest $request)
    {
        abort_unless(auth()->user()->canAny([
            TicketingPermission::ViewPersonalTicket->value,
            TicketingPermission::ViewDivisionTicket->value,
            TicketingPermission::ViewAllTicket->value,
        ]), 403);

        $dto = StoreTicketDTO::fromRequest($request);
        $this->ticketService->store($dto);

        return to_route('ticketing.tickets.index')->with('success', 'Laporan masalah berhasil diajukan.');
    }

    public function show(int $id)
    {
        abort_unless(auth()->user()->canAny([
            TicketingPermission::ViewPersonalTicket->value,
            TicketingPermission::ViewDivisionTicket->value,
            TicketingPermission::ViewAllTicket->value,
        ]), 403);

        $ticket = $this->ticketService->findById($id);
        abort_unless($ticket, 404);

        $refinementCount = $ticket->refinements()->count();

        return Inertia::render('Ticketing/Ticket/Show', [
            'ticket' => [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'description' => $ticket->description,
                'status' => [
                    'value' => $ticket->status->value,
                    'label' => $ticket->status->label(),
                ],
                'priority' => $ticket->priority ? [
                    'value' => $ticket->priority->value,
                    'label' => $ticket->priority->label(),
                ] : null,
                'real_priority' => $ticket->real_priority ? [
                    'value' => $ticket->real_priority->value,
                    'label' => $ticket->real_priority->label(),
                ] : null,
                'priority_reason' => $ticket->priority_reason,
                'asset_item' => [
                    'id' => $ticket->assetItem->id,
                    'category_name' => $ticket->assetItem->assetCategory?->name,
                    'merk' => $ticket->assetItem->merk,
                    'model' => $ticket->assetItem->model,
                    'serial_number' => $ticket->assetItem->serial_number,
                ],
                'attachments' => $ticket->attachments,
                'diagnose' => $ticket->diagnose,
                'follow_up' => $ticket->follow_up,
                'note' => $ticket->note,
                'confirm_note' => $ticket->confirm_note,
                'process_note' => $ticket->process_note,
                'process_attachments' => $ticket->process_attachments,
                'rating' => $ticket->rating,
                'feedback_description' => $ticket->feedback_description,
                'user' => $ticket->user?->name,
                'user_id' => $ticket->user_id,
                'confirmed_by' => $ticket->confirmedByUser?->name,
                'processed_by' => $ticket->processedByUser?->name,
                'confirmed_at' => $ticket->confirmed_at?->format('Y-m-d H:i'),
                'processed_at' => $ticket->processed_at?->format('Y-m-d H:i'),
                'finished_at' => $ticket->finished_at?->format('Y-m-d H:i'),
                'closed_at' => $ticket->closed_at?->format('Y-m-d H:i'),
                'created_at' => $ticket->created_at?->format('Y-m-d H:i'),
                'has_refinement' => $refinementCount > 0,
            ],
            'refinementCount' => $refinementCount,
            'priorities' => array_map(fn ($p) => ['value' => $p->value, 'label' => $p->label()], TicketPriority::cases()),
        ]);
    }

    public function confirmForm(Request $request, int $id, string $type)
    {
        abort_unless(auth()->user()->can(TicketingPermission::ConfirmTicket->value), 403);

        $ticket = $this->ticketService->findById($id);
        abort_unless($ticket, 404);

        if ($ticket->status !== TicketStatus::PENDING) {
            return to_route('ticketing.tickets.index')->with('error', 'Tiket sudah tidak bisa dikonfirmasi.');
        }

        $priorities = collect(TicketPriority::cases())->map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ]);

        return Inertia::render('Ticketing/Ticket/Confirm', [
            'ticket' => [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'description' => $ticket->description,
                'priority' => $ticket->priority ? [
                    'value' => $ticket->priority->value,
                    'label' => $ticket->priority->label(),
                ] : null,
                'priority_reason' => $ticket->priority_reason,
                'asset_item' => [
                    'id' => $ticket->assetItem->id,
                    'category_name' => $ticket->assetItem->assetCategory?->name,
                    'merk' => $ticket->assetItem->merk,
                    'model' => $ticket->assetItem->model,
                    'serial_number' => $ticket->assetItem->serial_number,
                ],
                'note' => $ticket->note,
            ],
            'priorities' => $priorities,
            'action' => $type === 'reject' ? 'reject' : 'accept',
        ]);
    }

    public function confirm(ConfirmTicketRequest $request, int $id, string $type)
    {
        abort_unless(auth()->user()->can(TicketingPermission::ConfirmTicket->value), 403);

        try {
            $this->ticketService->confirm(
                $id,
                $request->validated('action'),
                $request->validated('note'),
                $request->validated('real_priority'),
                $request->validated('priority_reason'),
            );
            $message = $request->validated('action') === 'accept'
                ? 'Tiket berhasil dikonfirmasi dan sedang diproses.'
                : 'Tiket berhasil ditolak.';
            return to_route('ticketing.tickets.index')->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function process(int $id)
    {
        abort_unless(auth()->user()->can(TicketingPermission::ProcessTicket->value), 403);

        $ticket = $this->ticketService->findById($id);
        abort_unless($ticket, 404);

        return Inertia::render('Ticketing/Ticket/Process', [
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
                'attachments' => $ticket->attachments,
                'diagnose' => $ticket->diagnose,
                'follow_up' => $ticket->follow_up,
                'note' => $ticket->note,
                'confirm_note' => $ticket->confirm_note,
                'process_note' => $ticket->process_note,
                'process_attachments' => $ticket->process_attachments,
                'has_refinement' => $ticket->refinements()->exists(),
            ],
        ]);
    }

    public function storeProcess(ProcessTicketRequest $request, int $id)
    {
        abort_unless(auth()->user()->can(TicketingPermission::ProcessTicket->value), 403);

        try {
            $dto = ProcessTicketDTO::fromRequest($request);
            $this->ticketService->process($id, $dto);
            return to_route('ticketing.tickets.index')->with('success', 'Tiket berhasil diproses.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function close(int $id)
    {
        abort_unless(auth()->user()->can(TicketingPermission::FinishTicket->value), 403);

        try {
            $this->ticketService->close($id);
            return back()->with('success', 'Tiket berhasil ditutup.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function storeFeedback(TicketFeedbackRequest $request, int $id)
    {
        abort_unless(auth()->user()->can(TicketingPermission::FeedbackTicket->value), 403);

        try {
            $dto = TicketFeedbackDTO::fromRequest($request);
            $this->ticketService->storeFeedback($id, $dto);
            return to_route('ticketing.tickets.index')->with('success', 'Terima kasih atas feedback Anda!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function feedbackForm(int $id)
    {
        abort_unless(auth()->user()->can(TicketingPermission::FeedbackTicket->value), 403);

        $ticket = $this->ticketService->findById($id);
        abort_unless($ticket, 404);

        if ($ticket->status !== TicketStatus::CLOSED || $ticket->rating !== null || $ticket->user_id !== auth()->id()) {
            return to_route('ticketing.tickets.index')->with('error', 'Tidak dapat memberikan feedback untuk tiket ini.');
        }

        return Inertia::render('Ticketing/Ticket/Feedback', [
            'ticket' => [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'description' => $ticket->description,
                'asset_item' => [
                    'id' => $ticket->assetItem->id,
                    'category_name' => $ticket->assetItem->assetCategory?->name,
                    'merk' => $ticket->assetItem->merk,
                    'model' => $ticket->assetItem->model,
                    'serial_number' => $ticket->assetItem->serial_number,
                ],
            ],
        ]);
    }
}
