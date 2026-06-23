<?php

namespace App\Models;

use App\Traits\CreatedUpdatedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class LandingContent extends Model
{
    use CreatedUpdatedBy;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'file_url',
        'link',
        'position',
        'landing_content_type_id',
    ];

    protected $casts = [
        'created_at' => 'datetime:d/m/Y H:i:s',
        'updated_at' => 'datetime:d/m/Y H:i:s',
        'deleted_at' => 'datetime:d/m/Y H:i:s',
    ];

    protected $appends = ['image_url'];

    /**
     * Scope a query by searching the `name` column.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\LandingContent>  $query
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\LandingContent>
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->whereRaw('name ILIKE ?', ["%$search%"]);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->file_url
            ? Storage::url($this->file_url)
            : null;
    }

    /**
     * @return BelongsTo<LandingContentType, LandingContent>
     */
    public function landingContentType(): BelongsTo
    {
        return $this->belongsTo(LandingContentType::class);
    }
}
