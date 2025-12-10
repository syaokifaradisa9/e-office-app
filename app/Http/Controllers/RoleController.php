<?php

namespace App\Http\Controllers;

use App\Datatables\RoleDatatableService;
use App\DataTransferObjects\RoleDTO;
use App\Http\Requests\DatatableRequest;
use App\Http\Requests\RoleRequest;
use App\Services\RoleService;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(
        private RoleService $roleService,
        private RoleDatatableService $roleDatatableService,
    ) {}

    public function index()
    {
        return Inertia::render('Role/Index');
    }

    public function create()
    {
        return Inertia::render('Role/Create', [
            'permissionsGrouped' => $this->roleService->getPermissionsGrouped(),
        ]);
    }

    public function store(RoleRequest $request)
    {
        $this->roleService->store(
            RoleDTO::fromAppRequest($request)
        );

        return to_route('role.index')->with('success', 'Sukses menambah data role');
    }

    public function edit(Role $role)
    {
        return Inertia::render('Role/Create', [
            'role' => $role->load('permissions'),
            'permissionsGrouped' => $this->roleService->getPermissionsGrouped(),
        ]);
    }

    public function update(Role $role, RoleRequest $request)
    {
        $this->roleService->update(
            $role,
            RoleDTO::fromAppRequest($request)
        );

        return to_route('role.index')->with('success', 'Sukses mengubah data role');
    }

    public function delete(Role $role)
    {
        $this->roleService->delete($role);

        return to_route('role.index')->with('success', 'Sukses menghapus data role');
    }

    public function datatable(DatatableRequest $request)
    {
        return $this->roleDatatableService->getDatatable($request, $request->user());
    }

    public function printExcel(DatatableRequest $request)
    {
        return $this->roleDatatableService->printExcel($request, $request->user());
    }
}
