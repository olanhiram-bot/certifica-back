<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Activity
 *
 * @property-read int $id
 * @property-read string $name
 * @property-read string|null $description
 * @property-read string|null $file_url
 * @property-read string|null $image_url
 * @property-read string|null $file_url_content
 * @property-read string|null $url_content
 * @property-read mixed $created_at
 * @property-read mixed $updated_at
 * @property-read mixed|null $deleted_at
 * @property-read int|null $created_by
 * @property-read int|null $updated_by
 * @property-read int|null $deleted_by
 */
class ActivityResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'file_url' => $this->file_url,
            'image_url' => $this->image_url,
            'file_url_content' => $this->file_url_content,
            'url_content' => $this->file_url_content
                ? route('activities.content', ['activity' => $this->id])
                : null,
            // devolver como objeto el stampActivity asignado a esta actividad
            'stamp_activity' => $this->whenLoaded('stampsActivity', function ($stamps) {
                $first = $stamps->first();

                return $first ? new StampActivityResource($first) : null;
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'deleted_by' => $this->deleted_by,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
