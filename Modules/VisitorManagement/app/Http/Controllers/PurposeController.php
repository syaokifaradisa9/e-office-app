<?php

namespace Modules\VisitorManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\DatatableRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\VisitorManagement\DataTransferObjects\PurposeDTO;
use Modules\VisitorManagement\Datatables\PurposeDataTableService;
use Modules\VisitorManagement\Models\VisitorPurpose;
use Modules\VisitorManagement\Services\PurposeService;
use Modules\VisitorManagement\Enums\VisitorUserPermission;

class PurposeController extends Controller
{
    public function __construct(
        private PurposeService $purposeService,
        private PurposeDataTableService $dataTableService
    ) {}

    public function index(DatatableRequest $request)
    {
        $user = auth()->user();
        
        if (!$user->hasAnyPermission([
            VisitorUserPermission::ViewMaster->value,
            VisitorUserPermission::ManageMaster->value,
        ])) {
            abort(403);
        }

        return Inertia::render('VisitorManagement/Purpose/Index', [
            'purposes' => $this->dataTableService->getDatatable($request),
            'filters' => $request->only(['search', 'status']),
            'canManage' => $user->hasPermissionTo(VisitorUserPermission::ManageMaster->value),
        ]);
    }

    public function datatable(DatatableRequest $request)
    {
        $user = auth()->user();
        
        if (!$user->hasAnyPermission([
            VisitorUserPermission::ViewMaster->value,
            VisitorUserPermission::ManageMaster->value,
        ])) {
            abort(403);
        }

        return response()->json($this->dataTableService->getDatatable($request));
    }

    public function create()
    {
        $user = auth()->user();
        
        if (!$user->hasPermissionTo(VisitorUserPermission::ManageMaster->value)) {
            abort(403);
        }

        return Inertia::render('VisitorManagement/Purpose/Create');
    }

    public function edit(VisitorPurpose $purpose)
    {
        $user = auth()->user();
        
        if (!$user->hasPermissionTo(VisitorUserPermission::ManageMaster->value)) {
            abort(403);
        }

        return Inertia::render('VisitorManagement/Purpose/Create', [
            'purpose' => $purpose,
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        
        if (!$user->hasPermissionTo(VisitorUserPermission::ManageMaster->value)) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:visitor_purposes,name',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $dto = PurposeDTO::fromRequest($request);
        $this->purposeService->store($dto);

        return redirect()->route('visitor.purposes.index')->with('success', 'Keperluan kunjungan berhasil ditambahkan.');
    }

    public function update(Request $request, VisitorPurpose $purpose)
    {
        $user = auth()->user();
        
        if (!$user->hasPermissionTo(VisitorUserPermission::ManageMaster->value)) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:visitor_purposes,name,' . $purpose->id,
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $dto = PurposeDTO::fromRequest($request);
        $this->purposeService->update($purpose, $dto);

        return redirect()->route('visitor.purposes.index')->with('success', 'Keperluan kunjungan berhasil diperbarui.');
    }

    public function destroy(VisitorPurpose $purpose)
    {
        $user = auth()->user();
        
        if (!$user->hasPermissionTo(VisitorUserPermission::ManageMaster->value)) {
            abort(403);
        }

        // Check dependencies
        if ($this->purposeService->hasVisitors($purpose)) {
            return back()->withErrors(['error' => 'Keperluan ini sedang digunakan dan tidak dapat dihapus.']);
        }

        $this->purposeService->delete($purpose);

        return back()->with('success', 'Keperluan kunjungan berhasil dihapus.');
    }

    public function toggleStatus(VisitorPurpose $purpose)
    {
        $user = auth()->user();
        
        if (!$user->hasPermissionTo(VisitorUserPermission::ManageMaster->value)) {
            abort(403);
        }

        $this->purposeService->toggleStatus($purpose);

        return back()->with('success', 'Status keperluan berhasil diubah.');
    }

    public function printExcel()
    {
        $user = auth()->user();
        
        if (!$user->hasPermissionTo(VisitorUserPermission::ViewMaster->value)) {
            abort(403);
        }

        return $this->purposeService->exportExcel();
    }
}
