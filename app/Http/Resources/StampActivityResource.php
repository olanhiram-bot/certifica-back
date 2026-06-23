<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/**
 * @mixin \App\Models\StampActivity
 *
 * @property-read int $id
 * @property-read int $stamp_id
 * @property-read int $activity_id
 * @property-read int $stamp_activity_status_id
 * @property-read int $score
 * @property-read string|null $file_url
 * @property-read string|null $file_path,
 * @property-read string|null $file_name,
 * @property-read int $file_size,
 * @property-read string|null $extension,
 * @property-read string|null $unit_size,
 * @property-read bool $material_was_view,
 * @property-read mixed|null $upload_date,
 * @property-read int|null $created_by
 * @property-read int|null $updated_by
 * @property-read int|null $deleted_by
 * @property-read mixed|null $deleted_at
 * @property-read string|null $deleted_reason
 * @property-read mixed $created_at
 * @property-read mixed $updated_at
 */
class StampActivityResource extends JsonResource
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
            'stamp_id' => $this->stamp_id,
            'activity_id' => $this->activity_id,
            'stamp_activity_status_id' => $this->stamp_activity_status_id,
            'score' => $this->score,
            'file_url' => $this->file_path
                ? URL::temporarySignedRoute(
                    'stampActivities.serveFile',
                    now()->addMinutes(30),
                    ['stamp_activity' => $this->id],
                )
                : null,
            'file_path' => $this->file_path,
            'file_name' => $this->file_name,
            'file_size' => $this->file_size,
            'extension' => $this->extension,
            'unit_size' => $this->unit_size,
            'upload_date' => $this->upload_date,
            'material_was_view' => $this->material_was_view,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'deleted_by' => $this->deleted_by,
            'deleted_at' => $this->deleted_at,
            'deleted_reason' => $this->deleted_reason,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'status' => new StampActivityStatusResource($this->whenLoaded('status')),
            'activity' => new ActivityResource($this->whenLoaded('activity')),
            'stamp' => new StampResource($this->whenLoaded('stamp')),
        ];
    }
}
