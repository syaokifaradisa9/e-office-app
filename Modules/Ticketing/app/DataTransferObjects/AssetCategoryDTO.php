<?php

namespace Modules\Ticketing\DataTransferObjects;

use Illuminate\Support\Facades\Auth;
use Modules\Ticketing\Enums\AssetCategoryType;
use Modules\Ticketing\Enums\TicketingPermission;
use Modules\Ticketing\Http\Requests\StoreAssetCategoryRequest;
use Modules\Ticketing\Http\Requests\UpdateAssetCategoryRequest;

class AssetCategoryDTO
{
    public function __construct(
        public readonly string $name,
        public readonly AssetCategoryType $type,
        public readonly ?int $division_id,
        public readonly int $maintenance_count,
    ) {}

    public static function fromRequest(StoreAssetCategoryRequest|UpdateAssetCategoryRequest $request): self
    {
        $user = Auth::user();
        $divisionId = $request->validated('division_id') ? (int) $request->validated('division_id') : null;

        if ($user && !$user->hasPermissionTo(TicketingPermission::ViewAllAssetCategory->value)) {
            if ($user->hasPermissionTo(TicketingPermission::ViewAssetCategoryDivisi->value)) {
                $divisionId = (int) $user->division_id;
            }
        }

        return new self(
            name: $request->validated('name'),
            type: AssetCategoryType::from($request->validated('type')),
            division_id: $divisionId,
            maintenance_count: (int) $request->validated('maintenance_count'),
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type->value,
            'division_id' => $this->division_id,
            'maintenance_count' => $this->maintenance_count,
        ];
    }
}
