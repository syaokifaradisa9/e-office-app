<?php

namespace Modules\Ticketing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\DatatableRequest;
use Inertia\Inertia;
use Modules\Ticketing\Models\AssetModel;
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

    public function index(AssetModel $assetModel)
    {
        return Inertia::render('Ticketing/Checklist/Index', [
            'assetModel' => [
                'id' => $assetModel->id,
                'name' => $assetModel->name,
                'type' => $assetModel->type?->value,
                'division' => $assetModel->division?->name,
            ],
        ]);
    }

    public function create(AssetModel $assetModel)
    {
        return Inertia::render('Ticketing/Checklist/Create', [
            'assetModel' => [
                'id' => $assetModel->id,
                'name' => $assetModel->name,
            ],
        ]);
    }

    public function store(StoreChecklistRequest $request, AssetModel $assetModel)
    {
        $dto = ChecklistDTO::fromRequest($request);
        $this->checklistService->store($assetModel->id, $dto);

        return to_route('ticketing.asset-models.checklists.index', $assetModel->id)
            ->with('success', 'Checklist berhasil ditambahkan.');
    }

    public function edit(AssetModel $assetModel, Checklist $checklist)
    {
        return Inertia::render('Ticketing/Checklist/Edit', [
            'assetModel' => [
                'id' => $assetModel->id,
                'name' => $assetModel->name,
            ],
            'checklist' => [
                'id' => $checklist->id,
                'label' => $checklist->label,
                'description' => $checklist->description,
            ],
        ]);
    }

    public function update(UpdateChecklistRequest $request, AssetModel $assetModel, Checklist $checklist)
    {
        $dto = ChecklistDTO::fromRequest($request);
        $this->checklistService->update($checklist->id, $dto);

        return to_route('ticketing.asset-models.checklists.index', $assetModel->id)
            ->with('success', 'Checklist berhasil diperbarui.');
    }

    public function delete(AssetModel $assetModel, Checklist $checklist)
    {
        $this->checklistService->delete($checklist->id);

        return back()->with('success', 'Checklist berhasil dihapus.');
    }

    public function datatable(DatatableRequest $request, AssetModel $assetModel)
    {
        return $this->datatableService->getDatatable($request, $assetModel->id);
    }

    public function printExcel(DatatableRequest $request, AssetModel $assetModel)
    {
        return $this->datatableService->printExcel($request, $assetModel->id, $assetModel->name);
    }
}
