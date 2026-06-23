<?php

namespace App\Models;

use App\Traits\CreatedUpdatedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Activity extends Model
{
    use CreatedUpdatedBy;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'file_url',
        'file_url_content',
        'section_id',
    ];

    protected $casts = [
        'created_at' => 'datetime:d/m/Y H:i:s',
        'updated_at' => 'datetime:d/m/Y H:i:s',
        'deleted_at' => 'datetime:d/m/Y H:i:s',
    ];

    protected $appends = ['image_url', 'url_content'];

    public function getImageUrlAttribute(): ?string
    {
        return $this->file_url
            ? Storage::url($this->file_url)
            : null;
    }

    public function getUrlContentAttribute(): ?string
    {
        return $this->file_url_content
            ? Storage::url($this->file_url_content)
            : null;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Section, \App\Models\Activity>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\StampActivity>
     */
    public function stampsActivity(): HasMany
    {
        return $this->hasMany(StampActivity::class);
    }
}
