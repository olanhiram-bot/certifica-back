<?php

namespace App\Models;

use App\Traits\CreatedUpdatedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int|null $percent
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\StampActivity[] $stampsActivity
 */
class Stamp extends Model
{
    use CreatedUpdatedBy;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'is_active',
        'vigency_start_date',
        'vigency_end_date',
        'take_review_date',
        'reject_date',
        'percent',
        'percent_moduls',
        'percent_evaluations',
        'provider_id',
        'reviewer_id',
        'modality_type_id',
        'course_call_id',
        'stamp_status_id',
        'created_by',
        'updated_by',
        'deleted_by',
        'deleted_reason',
    ];

    protected $casts = [
        'take_review_date' => 'date',
        'reject_date' => 'date',
        'created_at' => 'datetime:d/m/Y H:i:s',
        'updated_at' => 'datetime:d/m/Y H:i:s',
        'deleted_at' => 'datetime:d/m/Y H:i:s',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Activity>
     */
    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class, 'stamp_activities')
            ->using(StampActivity::class)
            ->withPivot('stamp_activity_status_id', 'created_by', 'updated_by');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\StampActivity>
     */
    public function stampsActivity(): HasMany
    {
        return $this->hasMany(StampActivity::class);
    }

    /**
     * @return BelongsTo<User, Stamp>
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    /**
     * @return BelongsTo<StampStatus, Stamp>
     */
    public function stampStatus(): BelongsTo
    {
        return $this->belongsTo(StampStatus::class);
    }

    /**
     * @return BelongsTo<ModalityType, Stamp>
     */
    public function modalityType(): BelongsTo
    {
        return $this->belongsTo(ModalityType::class);
    }

    /**
     * @return BelongsTo<CourseCall, Stamp>
     */
    public function courseCall(): BelongsTo
    {
        return $this->belongsTo(CourseCall::class);
    }
}
