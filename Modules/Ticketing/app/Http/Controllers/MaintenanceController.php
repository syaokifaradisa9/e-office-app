<?php

namespace Modules\Ticketing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\DatatableRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Ticketing\Datatables\MaintenanceDatatableService;
use Modules\Ticketing\Services\MaintenanceService;
use Modules\Ticketing\Enums\TicketingPermission;

class MaintenanceController extends Controller
{
    public function __construct(
        private MaintenanceDatatableService $datatableService,
        private MaintenanceService $maintenanceService
    ) {}

    public function index()
    {
        abort_unless(auth()->user()->canAny([
            TicketingPermission::ViewDivisionMaintenance->value,
            TicketingPermission::ViewAllMaintenance->value,
            TicketingPermission::ViewPersonalAsset->value,
        ]), 403);

        $years = \Modules\Ticketing\Models\Maintenance::selectRaw('YEAR(estimation_date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        return Inertia::render('Ticketing/Maintenance/Index', [
            'years' => $years,
        ]);
    }

    public function datatable(DatatableRequest $request)
    {
        abort_unless(auth()->user()->canAny([
            TicketingPermission::ViewDivisionMaintenance->value,
            TicketingPermission::ViewAllMaintenance->value,
            TicketingPermission::ViewPersonalAsset->value,
        ]), 403);
        
        return response()->json($this->datatableService->getDatatable($request, auth()->user()));
    }

    public function printExcel(DatatableRequest $request)
    {
        abort_unless(auth()->user()->canAny([
            TicketingPermission::ViewDivisionMaintenance->value,
            TicketingPermission::ViewAllMaintenance->value,
            TicketingPermission::ViewPersonalAsset->value,
        ]), 403);

        return $this->datatableService->printExcel($request, auth()->user());
    }

    public function detail(int $id)
    {
        abort_unless(auth()->user()->canAny([
            TicketingPermission::ViewDivisionMaintenance->value,
            TicketingPermission::ViewAllMaintenance->value,
            TicketingPermission::ViewPersonalAsset->value,
        ]), 403);
        
        $maintenance = $this->maintenanceService->findById($id);
        
        $formattedMaintenance = $this->formatMaintenance($maintenance);

        return Inertia::render('Ticketing/Maintenance/Detail', [
            'maintenance' => $formattedMaintenance,
        ]);
    }

    public function process(int $id)
    {
        abort_unless(auth()->user()->can(TicketingPermission::ProsesMaintenance->value), 403);
        
        $maintenance = $this->maintenanceService->findById($id);
        
        if ($maintenance->status !== \Modules\Ticketing\Enums\MaintenanceStatus::CONFIRMED && !$this->maintenanceService->isActionable($maintenance)) {
            abort(403, 'Terdapat maintenance sebelumnya yang belum dikonfirmasi.');
        }

        return Inertia::render('Ticketing/Maintenance/Checklist', [
            'maintenance' => $this->formatMaintenance($maintenance),
        ]);
    }

    private function formatMaintenance($item)
    {
        $checklistResults = $item->checklist_results;
        
        // If has relationship data, use it (prioritize new table)
        if ($item->checklists->isNotEmpty()) {
            $checklistResults = $item->checklists->map(fn($c) => [
                'checklist_id' => $c->checklist_id,
                'label' => $c->label,
                'description' => $c->description,
                'value' => $c->value === 'Good' ? 'Baik' : 'Tidak Baik',
                'note' => $c->note,
                'follow_up' => $c->followup,
            ]);
        }

        return [
            'id' => $item->id,
            'asset_item' => [
                'id' => $item->assetItem->id,
                'category_name' => $item->assetItem->assetCategory?->name,
                'merk' => $item->assetItem->merk,
                'model' => $item->assetItem->model,
                'serial_number' => $item->assetItem->serial_number,
                'asset_category' => [
                    'checklists' => $item->assetItem->assetCategory?->checklists?->map(fn($c) => [
                        'id' => $c->id,
                        'label' => $c->label,
                        'description' => $c->description,
                    ]),
                ],
            ],
            'estimation_date' => $item->estimation_date->format('Y-m-d'),
            'actual_date' => $item->actual_date?->format('Y-m-d'),
            'status' => [
                'value' => $item->status->value,
                'label' => $item->status->label(),
            ],
            'note' => $item->note,
            'checklist_results' => $checklistResults,
            'attachments' => $item->attachments,
            'user' => $item->user?->name,
        ];
    }

    public function storeChecklist(\Modules\Ticketing\Http\Requests\MaintenanceChecklistRequest $request, int $id)
    {
        abort_unless(auth()->user()->can(TicketingPermission::ProsesMaintenance->value), 403);
        
        $maintenance = $this->maintenanceService->findById($id);
        if (!$this->maintenanceService->isActionable($maintenance)) {
            abort(403, 'Terdapat maintenance sebelumnya yang belum dikonfirmasi.');
        }

        $dto = \Modules\Ticketing\DataTransferObjects\MaintenanceChecklistDTO::fromRequest($request);
        $this->maintenanceService->saveChecklist($id, $dto);

        return to_route('ticketing.maintenances.index')
            ->with('success', 'Checklist maintenance berhasil disimpan.');
    }

    public function cancel(Request $request, int $id)
    {
        abort_unless(auth()->user()->can(TicketingPermission::ManageAsset->value), 403);

        $request->validate([
            'note' => 'required|string',
        ]);

        $this->maintenanceService->cancel($id, $request->note);

        return back()->with('flash', [
            'type' => 'success',
            'message' => 'Maintenance berhasil dibatalkan.'
        ]);
    }

    public function confirm(int $id)
    {
        abort_unless(auth()->user()->can(TicketingPermission::ConfirmMaintenance->value), 403);

        try {
            $this->maintenanceService->confirm($id);
            return back()->with('flash', [
                'type' => 'success',
                'message' => 'Maintenance berhasil dikonfirmasi.'
            ]);
        } catch (\Exception $e) {
            return back()->with('flash', [
                'type' => 'danger',
                'message' => $e->getMessage()
            ]);
        }
    }
}
