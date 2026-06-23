<?php

namespace App\Models;

use App\Traits\CreatedUpdatedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class StampActivity extends Pivot
{
    use CreatedUpdatedBy;
    use HasFactory;
    use SoftDeletes;

    protected $table = 'stamp_activities';
    public $incrementing = true;
    public $timestamps = true;
    protected $fillable = [
        'stamp_id',
        'activity_id',
        'file_path',
        'file_name',
        'file_size',
        'extension',
        'unit_size',
        'upload_date',
        'material_was_view',
        'stamp_activity_status_id',
        'created_by',
        'updated_by',
        'deleted_by',
        'deleted_reason',
    ];

    protected $casts = [
        'created_at' => 'datetime:d/m/Y H:i:s',
        'updated_at' => 'datetime:d/m/Y H:i:s',
        'deleted_at' => 'datetime:d/m/Y H:i:s',
        'upload_date' => 'datetime:d/m/Y H:i:s',
    ];

    protected $appends = ['file_url'];

    public function getFileUrlAttribute(): ?string
    {
        return $this->file_path
            ? Storage::url($this->file_path)
            : null;
    }

    /**
     * @return BelongsTo<Stamp, StampActivity>
     */
    public function stamp(): BelongsTo
    {
        return $this->belongsTo(Stamp::class);
    }

    /**
     * @return BelongsTo<Activity, StampActivity>
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }

    /**
     * @return BelongsTo<StampActivityStatus, StampActivity>
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(StampActivityStatus::class, 'stamp_activity_status_id');
    }

    /**
     * @return HasMany<StampActivityComment>
     */
    public function stampActivityComments(): HasMany
    {
        return $this->hasMany(StampActivityComment::class, 'stamp_activity_id');
    }

    /**
     * Filtra por nombre de actividad o de sección.
     *
     * @param  Builder<StampActivity>  $query
     * @return Builder<StampActivity>
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (! $search) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search) {
            $q->whereHas('activity', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"))
                ->orWhereHas('activity.section', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"));
        });
    }
}
