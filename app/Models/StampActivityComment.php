<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StampActivityComment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'stamp_activity_id',
        'comment',
        'created_by',
        'updated_by',
        'deleted_by',
        'deleted_reason',
    ];

    protected $casts = [
        'created_at' => 'datetime:d/m/Y H:i:s',
        'updated_at' => 'datetime:d/m/Y H:i:s',
        'deleted_at' => 'datetime:d/m/Y H:i:s',
    ];

    /**
     * @return BelongsTo<StampActivity, StampActivityComment>
     */
    public function stampActivity(): BelongsTo
    {
        return $this->belongsTo(StampActivity::class);
    }
}
