<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property string $token
 * @property \App\Models\User $user
 * @property array<mixed> $permissions
 */
class AuthResource extends JsonResource
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
        return [
            'token' => $this->token,
            'user' => $this->user,
            'permissions' => $this->permissions,
        ];
    }
}
