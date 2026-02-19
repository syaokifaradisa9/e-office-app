<?php

namespace Modules\Ticketing\DataTransferObjects;

use Illuminate\Http\Request;

class AssetItemDTO
{
    public function __construct(
        public readonly int $asset_model_id,
        public readonly ?string $merk,
        public readonly ?string $model,
        public readonly ?string $serial_number,
        public readonly int $division_id,
        public readonly ?array $another_attributes,
        public readonly array $user_ids = [],
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            asset_model_id: (int) $request->validated('asset_model_id'),
            merk: $request->validated('merk'),
            model: $request->validated('model'),
            serial_number: $request->validated('serial_number'),
            division_id: (int) $request->validated('division_id'),
            another_attributes: $request->validated('another_attributes') ?? [],
            user_ids: $request->validated('user_ids') ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'asset_model_id' => $this->asset_model_id,
            'merk' => $this->merk,
            'model' => $this->model,
            'serial_number' => $this->serial_number,
            'division_id' => $this->division_id,
            'another_attributes' => $this->another_attributes,
        ];
    }
}
