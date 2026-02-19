<?php

namespace Modules\Ticketing\DataTransferObjects;

use Illuminate\Support\Facades\Auth;
use Modules\Ticketing\Enums\AssetModelType;
use Modules\Ticketing\Enums\TicketingPermission;
use Modules\Ticketing\Http\Requests\StoreAssetModelRequest;
use Modules\Ticketing\Http\Requests\UpdateAssetModelRequest;

class AssetModelDTO
{
    public function __construct(
        public readonly string $name,
        public readonly AssetModelType $type,
        public readonly int $division_id,
    ) {}

    public static function fromRequest(StoreAssetModelRequest|UpdateAssetModelRequest $request): self
    {
        $user = Auth::user();
        $divisionId = (int) $request->validated('division_id');

        if ($user && !$user->hasPermissionTo(TicketingPermission::ViewAllAssetModel)) {
            if ($user->hasPermissionTo(TicketingPermission::ViewAssetModelDivisi)) {
                $divisionId = (int) $user->division_id;
            }
        }

        return new self(
            name: $request->validated('name'),
            type: AssetModelType::from($request->validated('type')),
            division_id: $divisionId,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type->value,
            'division_id' => $this->division_id,
        ];
    }
}
