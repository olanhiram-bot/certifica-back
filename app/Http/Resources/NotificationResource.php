<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Notification
 *
 * @property-read int $id
 * @property-read int $user_id
 * @property-read string|null $title
 * @property-read string|null $description
 * @property-read string|null $link
 * @property-read bool $is_read
 * @property-read bool $is_view
 * @property-read mixed $created_at
 * @property-read mixed $updated_at
 * @property-read int|null $created_by
 * @property-read int|null $updated_by
 */
class NotificationResource extends JsonResource
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
            'id' => $this->id,
            'user_id' => $this->user_id,
            'title' => $this->title,
            'description' => $this->description,
            'link' => $this->link,
            'is_read' => $this->is_read,
            'is_view' => $this->is_view,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'unread' => $this->is_read,
            'unview' => $this->is_view,
        ];

    }
}
