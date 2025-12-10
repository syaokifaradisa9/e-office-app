<?php

namespace App\Http\Controllers;

use App\Datatables\PositionDatatableService;
use App\DataTransferObjects\PositionDTO;
use App\Http\Requests\DatatableRequest;
use App\Http\Requests\PositionRequest;
use App\Models\Position;
use App\Services\PositionService;
use Inertia\Inertia;

class PositionController extends Controller
{
    public function __construct(
        private PositionService $positionService,
        private PositionDatatableService $positionDatatableService,
    ) {}

    public function index()
    {
        return Inertia::render('Position/Index');
    }

    public function create()
    {
        return Inertia::render('Position/Create');
    }

    public function store(PositionRequest $request)
    {
        $this->positionService->store(
            PositionDTO::fromAppRequest($request)
        );

        return to_route('position.index')->with('success', 'Sukses menambah data jabatan');
    }

    public function edit(Position $position)
    {
        return Inertia::render('Position/Create', [
            'position' => $position,
        ]);
    }

    public function update(Position $position, PositionRequest $request)
    {
        $this->positionService->update(
            $position,
            PositionDTO::fromAppRequest($request)
        );

        return to_route('position.index')->with('success', 'Sukses mengubah data jabatan');
    }

    public function delete(Position $position)
    {
        $this->positionService->delete($position);

        return to_route('position.index')->with('success', 'Sukses menghapus data jabatan');
    }

    public function datatable(DatatableRequest $request)
    {
        return $this->positionDatatableService->getDatatable($request, $request->user());
    }

    public function printExcel(DatatableRequest $request)
    {
        return $this->positionDatatableService->printExcel($request, $request->user());
    }
}
