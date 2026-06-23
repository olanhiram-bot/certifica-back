<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Stamp
 */
class ProviderStampResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function toArray(Request $request): array
    {
        $providerUser = $this->provider?->user ?? [];

        $takeReviewDate = $this->take_review_date;
        $daysElapsed = $takeReviewDate ? (int) now()->startOfDay()->diffInDays($takeReviewDate->copy()->startOfDay()) : 0;
        $remainingDays = $takeReviewDate ? max(0, 10 - $daysElapsed) : null;

        return [
            'id' => $this->id,
            'vigency_start_date' => $this->vigency_start_date,
            'vigency_end_date' => $this->vigency_end_date,
            'percent' => $this->percent,
            'provider' => [
                'id' => $this->provider?->id,
                'username' => $this->provider?->username,
                'email' => $providerUser['email'] ?? null,
                'rfc' => $providerUser['rfc'] ?? null,
                'name' => $providerUser['provider_info']['nombre_completo'] ?? null,
            ],
            'stamp_status' => [
                'id' => $this->stampStatus?->id,
                'name' => $this->stampStatus?->name,
                'color' => $this->stampStatus?->hexadecimal,
            ],
            'modality_type' => [
                'id' => $this->modalityType?->id,
                'name' => $this->modalityType?->name,
            ],
            'created_at' => $this->created_at,
            'remaining_days' => $remainingDays,
            'reviewer_id' => $this->reviewer_id,
        ];
    }
}
