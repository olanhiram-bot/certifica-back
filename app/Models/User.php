<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property string $email
 */
class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'password',
        'user',
        'auth_method_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'auth_method_id' => 1,
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'user' => 'array',
    ];

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        $search = mb_strtolower($search);
        $grammar = $query->getQuery()->getGrammar();

        return $query
            ->whereRaw('LOWER(username) LIKE ?', ["%{$search}%"])
            ->orWhereRaw('LOWER('.$grammar->wrap('user->email').') LIKE ?', ["%{$search}%"])
            ->orWhereRaw('LOWER('.$grammar->wrap('user->rfc').') LIKE ?', ["%{$search}%"])
            ->orWhereRaw('LOWER('.$grammar->wrap('user->provider_info->nombre_completo').') LIKE ?', ["%{$search}%"]);
    }

    /**
     * @return BelongsTo<AuthMethod, User>
     */
    public function authMethod(): BelongsTo
    {
        return $this->belongsTo(AuthMethod::class);
    }

    /**
     * @return HasMany<Stamp>
     */
    public function stamps(): HasMany
    {
        return $this->hasMany(Stamp::class, 'provider_id');
    }
}
