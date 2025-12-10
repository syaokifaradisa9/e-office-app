<?php

namespace App\Http\Controllers;

use App\Datatables\DivisionDatatableService;
use App\DataTransferObjects\DivisionDTO;
use App\Http\Requests\DatatableRequest;
use App\Http\Requests\DivisionRequest;
use App\Models\Division;
use App\Services\DivisionService;
use Inertia\Inertia;

class DivisionController extends Controller
{
    public function __construct(
        private DivisionService $divisionService,
        private DivisionDatatableService $divisionDatatableService,
    ) {}

    public function index()
    {
        return Inertia::render('Division/Index');
    }

    public function create()
    {
        return Inertia::render('Division/Create');
    }

    public function store(DivisionRequest $request)
    {
        $this->divisionService->store(
            DivisionDTO::fromAppRequest($request)
        );

        return to_route('division.index')->with('success', 'Sukses menambah data divisi');
    }

    public function edit(Division $division)
    {
        return Inertia::render('Division/Create', [
            'division' => $division,
        ]);
    }

    public function update(Division $division, DivisionRequest $request)
    {
        $this->divisionService->update(
            $division,
            DivisionDTO::fromAppRequest($request)
        );

        return to_route('division.index')->with('success', 'Sukses mengubah data divisi');
    }

    public function delete(Division $division)
    {
        $this->divisionService->delete($division);

        return to_route('division.index')->with('success', 'Sukses menghapus data divisi');
    }

    public function datatable(DatatableRequest $request)
    {
        return $this->divisionDatatableService->getDatatable($request, $request->user());
    }

    public function printExcel(DatatableRequest $request)
    {
        return $this->divisionDatatableService->printExcel($request, $request->user());
    }
}
