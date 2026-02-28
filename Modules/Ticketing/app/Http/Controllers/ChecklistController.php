<?php

namespace Modules\Ticketing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\DatatableRequest;
use Inertia\Inertia;
use Modules\Ticketing\Models\AssetCategory;
use Modules\Ticketing\Models\Checklist;
use Modules\Ticketing\Services\ChecklistService;
use Modules\Ticketing\Datatables\ChecklistDatatableService;
use Modules\Ticketing\Http\Requests\StoreChecklistRequest;
use Modules\Ticketing\Http\Requests\UpdateChecklistRequest;
use Modules\Ticketing\DataTransferObjects\ChecklistDTO;

class ChecklistController extends Controller
{
    public function __construct(
        private ChecklistService $checklistService,
        private ChecklistDatatableService $datatableService,
    ) {}

    public function index(AssetCategory $assetCategory)
    {
        return Inertia::render('Ticketing/Checklist/Index', [
            'assetCategory' => [
                'id' => $assetCategory->id,
                'name' => $assetCategory->name,
                'type' => $assetCategory->type?->value,
                'division' => $assetCategory->division?->name,
            ],
        ]);
    }

    public function create(AssetCategory $assetCategory)
    {
        return Inertia::render('Ticketing/Checklist/Create', [
            'assetCategory' => [
                'id' => $assetCategory->id,
                'name' => $assetCategory->name,
            ],
        ]);
    }

    public function store(StoreChecklistRequest $request, AssetCategory $assetCategory)
    {
        $dto = ChecklistDTO::fromRequest($request);
        $this->checklistService->store($assetCategory->id, $dto);

        return to_route('ticketing.asset-categories.checklists.index', $assetCategory->id)
            ->with('success', 'Checklist berhasil ditambahkan.');
    }

    public function edit(AssetCategory $assetCategory, Checklist $checklist)
    {
        return Inertia::render('Ticketing/Checklist/Edit', [
            'assetCategory' => [
                'id' => $assetCategory->id,
                'name' => $assetCategory->name,
            ],
            'checklist' => [
                'id' => $checklist->id,
                'label' => $checklist->label,
                'description' => $checklist->description,
            ],
        ]);
    }

    public function update(UpdateChecklistRequest $request, AssetCategory $assetCategory, Checklist $checklist)
    {
        $dto = ChecklistDTO::fromRequest($request);
        $this->checklistService->update($checklist->id, $dto);

        return to_route('ticketing.asset-categories.checklists.index', $assetCategory->id)
            ->with('success', 'Checklist berhasil diperbarui.');
    }

    public function delete(AssetCategory $assetCategory, Checklist $checklist)
    {
        $this->checklistService->delete($checklist->id);

        return back()->with('success', 'Checklist berhasil dihapus.');
    }

    public function datatable(DatatableRequest $request, AssetCategory $assetCategory)
    {
        return $this->datatableService->getDatatable($request, $assetCategory->id);
    }

    public function printExcel(DatatableRequest $request, AssetCategory $assetCategory)
    {
        return $this->datatableService->printExcel($request, $assetCategory->id, $assetCategory->name);
    }
}
