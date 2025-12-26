<?php

namespace Modules\Archieve\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Modules\Archieve\DataTransferObjects\StoreDivisionStorageDTO;
use Modules\Archieve\Http\Requests\StoreDivisionStorageRequest;
use Modules\Archieve\Models\DivisionStorage;
use Modules\Archieve\Services\DivisionStorageService;
use Modules\Archieve\Enums\ArchieveUserPermission;
use Illuminate\Support\Facades\Gate;

class DivisionStorageController extends Controller
{
    public function __construct(
        private DivisionStorageService $storageService
    ) {}

    public function index()
    {
        Gate::authorize(ArchieveUserPermission::ViewDivisionStorage->value);

        return Inertia::render('Archieve/DivisionStorage/Index', [
            'divisionsWithStorage' => $this->storageService->getDivisionsWithStorage(),
        ]);
    }

    public function store(StoreDivisionStorageRequest $request)
    {
        $dto = StoreDivisionStorageDTO::fromRequest($request);
        $this->storageService->store($dto);

        return back()->with('success', 'Kuota penyimpanan divisi berhasil diperbarui.');
    }

    public function update(StoreDivisionStorageRequest $request, DivisionStorage $divisionStorage)
    {
        $dto = StoreDivisionStorageDTO::fromRequest($request);
        $this->storageService->update($divisionStorage, $dto);

        return back()->with('success', 'Kuota penyimpanan divisi berhasil diperbarui.');
    }

    public function destroy(DivisionStorage $divisionStorage)
    {
        Gate::authorize(ArchieveUserPermission::ManageDivisionStorage->value);

        $this->storageService->delete($divisionStorage);

        return back()->with('success', 'Kuota penyimpanan divisi berhasil dihapus.');
    }
}
