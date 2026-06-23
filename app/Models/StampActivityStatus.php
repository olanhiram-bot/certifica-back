<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int $stamp_activities_count
 */
class StampActivityStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'hexadecimal',
        'section_type_id',
    ];

    protected $casts = [
        'created_at' => 'datetime:d/m/Y H:i:s',
        'updated_at' => 'datetime:d/m/Y H:i:s',
        'deleted_at' => 'datetime:d/m/Y H:i:s',
    ];

    /** @return HasMany<StampActivity> */
    public function stampActivities(): HasMany
    {
        return $this->hasMany(StampActivity::class, 'stamp_activity_status_id');
    }
}
