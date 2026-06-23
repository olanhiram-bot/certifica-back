<?php

namespace App\Http\Resources;

use App\Models\StampStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StampStatus
 */
class StampStatusResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => data_get($this->resource, 'id'),
            'name' => data_get($this->resource, 'name'),
            'hexadecimal' => data_get($this->resource, 'hexadecimal'),
            'providers_count' => data_get($this->resource, 'providers_count', 0),
        ];
    }
}
