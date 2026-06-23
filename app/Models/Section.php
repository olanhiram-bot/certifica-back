<?php

namespace App\Models;

use App\Traits\CreatedUpdatedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Section extends Model
{
    use CreatedUpdatedBy;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'section_type_id',
    ];

    protected $casts = [
        'created_at' => 'datetime:d/m/Y H:i:s',
        'updated_at' => 'datetime:d/m/Y H:i:s',
        'deleted_at' => 'datetime:d/m/Y H:i:s',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Activity>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'section_id');

    }
}
