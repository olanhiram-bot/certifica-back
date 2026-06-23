<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Stamp
 *
 * @property-read int $id
 * @property-read bool $is_active
 * @property-read string|null $vigency_start_date
 * @property-read string|null $vigency_end_date
 * @property-read int|null $percent
 * @property-read int|null $percent_moduls
 * @property-read int|null $percent_evaluations
 * @property-read int|null $stamp_status_id
 * @property-read mixed|null $reject_date
 * @property-read int|null $provider_id
 * @property-read int|null $reviewer_id
 * @property-read int|null $modality_type_id
 * @property-read mixed $created_at
 * @property-read mixed $updated_at
 * @property-read mixed|null $deleted_at
 * @property-read int|null $created_by
 * @property-read int|null $updated_by
 * @property-read int|null $deleted_by
 */
class StampResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function toArray(Request $request): array
    {
        $providerUser = $this->provider?->user ?? [];

        $vigencyDate = $this->stamp_status_id !== 6 ? $this->created_at : $this->reject_date;
        $daysElapsed = $vigencyDate ? (int) now()->startOfDay()->diffInDays($vigencyDate->copy()->startOfDay()) : 0;
        $remainingDays = $vigencyDate ? max(0, 10 - $daysElapsed) : null;

        return [
            'id' => $this->id,
            'is_active' => $this->is_active,
            'vigency_start_date' => $this->vigency_start_date,
            'vigency_end_date' => $this->vigency_end_date,
            'percent' => $this->percent,
            'percent_moduls' => $this->percent_moduls,
            'percent_evaluations' => $this->percent_evaluations,
            'provider_id' => $this->provider_id,
            'modality_type_id' => $this->modality_type_id,
            'stamp_status_id' => $this->stamp_status_id,
            'reviewer_id' => $this->reviewer_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            'deleted_reason' => $this->deleted_reason,
            'remaining_days' => $remainingDays,
            'provider' => $this->whenLoaded('provider', fn () => [
                'id' => $this->provider?->id,
                'username' => $this->provider?->username,
                'email' => $providerUser['email'] ?? null,
                'rfc' => $providerUser['rfc'] ?? null,
                'name' => $providerUser['provider_info']['nombre_completo'] ?? null,
            ]),
            'stamp_status' => $this->whenLoaded('stampStatus', fn () => [
                'id' => $this->stampStatus?->id,
                'name' => $this->stampStatus?->name,
                'color' => $this->stampStatus?->hexadecimal,
            ]),
        ];
    }
}
