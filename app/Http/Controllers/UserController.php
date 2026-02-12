<?php

namespace App\Http\Controllers;

use App\Datatables\UserDatatableService;
use App\DataTransferObjects\UserDTO;
use App\Http\Requests\DatatableRequest;
use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Services\DivisionService;
use App\Services\PositionService;
use App\Services\RoleService;
use App\Services\UserService;
use Inertia\Inertia;

class UserController extends Controller
{
    public function __construct(
        private UserService $userService,
        private UserDatatableService $userDatatableService,
        private DivisionService $divisionService,
        private PositionService $positionService,
        private RoleService $roleService,
    ) {}

    public function index()
    {
        return Inertia::render('User/Index', [
            'divisions' => $this->divisionService->getActive(),
            'positions' => $this->positionService->getActive(),
        ]);
    }

    public function create()
    {
        return Inertia::render('User/Create', [
            'divisions' => $this->divisionService->getActive(),
            'positions' => $this->positionService->getActive(),
            'roles' => $this->roleService->getAll(),
        ]);
    }

    public function store(UserRequest $request)
    {
        $this->userService->store(
            UserDTO::fromAppRequest($request)
        );

        return to_route('user.index')->with('success', 'Sukses menambah data pengguna');
    }

    public function edit(User $user)
    {
        return Inertia::render('User/Create', [
            'user' => $user->load(['division', 'position', 'roles']),
            'divisions' => $this->divisionService->getActive(),
            'positions' => $this->positionService->getActive(),
            'roles' => $this->roleService->getAll(),
        ]);
    }

    public function update(User $user, UserRequest $request)
    {
        $this->userService->update(
            $user,
            UserDTO::fromAppRequest($request)
        );

        return to_route('user.index')->with('success', 'Sukses mengubah data pengguna');
    }

    public function delete(User $user)
    {
        $this->userService->delete($user);

        return to_route('user.index')->with('success', 'Sukses menghapus data pengguna');
    }

    public function datatable(DatatableRequest $request)
    {
        return $this->userDatatableService->getDatatable($request, $request->user());
    }

    public function printExcel(DatatableRequest $request, $type)
    {
        return $this->userDatatableService->printExcel($request, $request->user());
    }
}
