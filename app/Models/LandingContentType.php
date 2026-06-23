<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LandingContentType extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'landing_content_types';

    protected $fillable = [
        'name',
    ];

    protected $casts = [
        'created_at' => 'datetime:d/m/Y H:i:s',
        'updated_at' => 'datetime:d/m/Y H:i:s',
        'deleted_at' => 'datetime:d/m/Y H:i:s',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\LandingContent>
     */
    public function landingContents(): HasMany
    {
        return $this->hasMany(LandingContent::class, 'landing_content_type_id');
    }
}
