<?php

namespace Modules\Ticketing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Ticketing\Services\MaintenanceService;
use Modules\Ticketing\Enums\TicketingPermission;
use Modules\Ticketing\Enums\MaintenanceStatus;

class RefinementController extends Controller
{
    public function __construct(
        private MaintenanceService $maintenanceService,
        private \Modules\Ticketing\Datatables\MaintenanceRefinementDatatableService $datatableService
    ) {}

    public function datatable(\App\Http\Requests\DatatableRequest $request, int $id)
    {
        abort_unless(auth()->user()->canAny([
            TicketingPermission::ProsesMaintenance->value,
            TicketingPermission::ViewDivisionMaintenance->value,
            TicketingPermission::ViewAllMaintenance->value,
            TicketingPermission::ViewPersonalAsset->value,
        ]), 403);
        return response()->json($this->datatableService->getDatatable($request, $id));
    }

    public function index(int $id)
    {
        abort_unless(auth()->user()->can(TicketingPermission::ProsesMaintenance->value), 403);
        
        $maintenance = $this->maintenanceService->findById($id);
        
        if (!$maintenance || $maintenance->status !== MaintenanceStatus::REFINEMENT) {
            return to_route('ticketing.maintenances.index')->with('error', 'Data maintenance tidak ditemukan atau status tidak sesuai.');
        }

        return Inertia::render('Ticketing/Maintenance/Refinement', [
            'maintenance' => $this->formatMaintenance($maintenance),
            'refinements' => $maintenance->refinements,
        ]);
    }

    public function create(int $id)
    {
        abort_unless(auth()->user()->can(TicketingPermission::ProsesMaintenance->value), 403);
        
        $maintenance = $this->maintenanceService->findById($id);
        
        if (!$maintenance || $maintenance->status !== MaintenanceStatus::REFINEMENT) {
            return to_route('ticketing.maintenances.index')->with('error', 'Data maintenance tidak ditemukan atau status tidak sesuai.');
        }

        return Inertia::render('Ticketing/Maintenance/RefinementAdd', [
            'maintenance' => $this->formatMaintenance($maintenance),
        ]);
    }

    public function store(Request $request, int $id)
    {
        abort_unless(auth()->user()->can(TicketingPermission::ProsesMaintenance->value), 403);

        $request->validate([
            'date' => 'required|date',
            'description' => 'required|string',
            'note' => 'nullable|string',
            'result' => 'required|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:5120', // 5MB limit
        ]);

        $this->maintenanceService->saveRefinement($id, $request->all());

        return to_route('ticketing.maintenances.refinement.index', $id)->with('success', 'Rincian perbaikan berhasil disimpan.');
    }

    public function delete(int $id)
    {
        abort_unless(auth()->user()->can(TicketingPermission::ProsesMaintenance->value), 403);
        $this->maintenanceService->deleteRefinement($id);
        return back()->with('success', 'Rincian perbaikan berhasil dihapus.');
    }

    public function edit(int $id)
    {
        abort_unless(auth()->user()->can(TicketingPermission::ProsesMaintenance->value), 403);
        
        $refinement = $this->maintenanceService->findRefinementById($id);
        if (!$refinement) {
            return back()->with('error', 'Data perbaikan tidak ditemukan.');
        }

        $maintenance = $this->maintenanceService->findById($refinement->maintenance_id);
        if (!$maintenance || $maintenance->status !== MaintenanceStatus::REFINEMENT) {
            return to_route('ticketing.maintenances.index')->with('error', 'Data maintenance tidak ditemukan atau status tidak sesuai.');
        }

        return Inertia::render('Ticketing/Maintenance/RefinementUpdate', [
            'maintenance' => $this->formatMaintenance($maintenance),
            'refinement' => $refinement,
        ]);
    }

    public function update(Request $request, int $id)
    {
        abort_unless(auth()->user()->can(TicketingPermission::ProsesMaintenance->value), 403);

        $request->validate([
            'date' => 'required|date',
            'description' => 'required|string',
            'note' => 'nullable|string',
            'result' => 'required|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:5120', // 5MB limit
        ]);

        $refinement = $this->maintenanceService->findRefinementById($id);
        $this->maintenanceService->updateRefinement($id, $request->all());

        return to_route('ticketing.maintenances.refinement.index', $refinement->maintenance_id)->with('success', 'Rincian perbaikan berhasil diperbarui.');
    }

    public function finish(int $id)
    {
        abort_unless(auth()->user()->can(TicketingPermission::ProsesMaintenance->value), 403);

        try {
            $this->maintenanceService->finishRefinement($id);
            return to_route('ticketing.maintenances.index')->with('success', 'Maintenance dan perbaikan telah diselesaikan.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    private function formatMaintenance($item)
    {
        $checklistResults = $item->checklist_results;
        
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
}
