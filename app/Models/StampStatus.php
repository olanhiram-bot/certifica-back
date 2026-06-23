<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StampStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'hexadecimal',
    ];

    protected $casts = [
        'created_at' => 'datetime:d/m/Y H:i:s',
        'updated_at' => 'datetime:d/m/Y H:i:s',
    ];

    /**
     * @return HasMany<Stamp>
     */
    public function stamps(): HasMany
    {
        return $this->hasMany(Stamp::class);
    }
}
