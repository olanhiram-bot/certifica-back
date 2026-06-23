<?php

namespace App\Models;

use App\Traits\CreatedUpdatedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseCall extends Model
{
    use CreatedUpdatedBy;
    use HasFactory;
    use SoftDeletes;

    protected $table = 'course_calls';

    protected $fillable = [
        'name',
        'vigency_start_date',
        'vigency_end_date',
    ];

    protected $casts = [
        'created_at' => 'datetime:d/m/Y H:i:s',
        'updated_at' => 'datetime:d/m/Y H:i:s',
        'deleted_at' => 'datetime:d/m/Y H:i:s',
        'vigency_start_date' => 'date',
        'vigency_end_date' => 'date',
    ];

    /**
     * Scope para filtrar convocatorias vigentes hoy.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\CourseCall>  $query
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\CourseCall>
     */
    public function scopeCurrentToday(Builder $query): Builder
    {
        $hoy = now();

        return $query->where('vigency_start_date', '<=', $hoy)
            ->where('vigency_end_date', '>=', $hoy);
    }
}
