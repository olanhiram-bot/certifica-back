<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\CourseCall
 *
 * @property-read int $id
 * @property-read string $name
 * @property-read mixed $vigency_start_date
 * @property-read mixed $vigency_end_date
 * @property-read mixed $created_at
 * @property-read mixed $updated_at
 * @property-read mixed|null $deleted_at
 * @property-read int|null $created_by
 * @property-read int|null $updated_by
 * @property-read int|null $deleted_by
 */
class CourseCallResource extends JsonResource
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
            'vigency_start_date' => $this->vigency_start_date,
            'vigency_end_date' => $this->vigency_end_date,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'deleted_by' => $this->deleted_by,
        ];
    }
}
