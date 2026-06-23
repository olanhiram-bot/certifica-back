<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\StampActivityComment
 *
 * @property-read int $id
 * @property-read int $stamp_activity_id
 * @property-read string $comment
 * @property-read int|null $created_by
 * @property-read int|null $updated_by
 * @property-read int|null $deleted_by
 * @property-read mixed|null $deleted_at
 * @property-read string|null $deleted_reason
 * @property-read mixed $created_at
 * @property-read mixed $updated_at
 */
class StampActivityCommentResource extends JsonResource
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
        /**
         * Transform the resource into an array.
         *
         * @return array<string, mixed>
         *
         * @SuppressWarnings(PHPMD.UnusedFormalParameter)
         */
        return [
            'id' => $this->id,
            'comment' => $this->comment,
            'stamp_activity_id' => $this->stamp_activity_id,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'deleted_by' => $this->deleted_by,
            'deleted_at' => $this->deleted_at,
            'deleted_reason' => $this->deleted_reason,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
