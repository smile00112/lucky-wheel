<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wheel extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'slug',
        'is_active',
        'settings',
        'spins_limit',
        'refresh_hour',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'spins_limit' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /**
     * Владелец колеса
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Призы колеса
     */
    public function prizes(): HasMany
    {
        return $this->hasMany(Prize::class)->orderBy('sort');
    }

    /**
     * Активные призы колеса
     */
    public function activePrizes(): HasMany
    {
        return $this->hasMany(Prize::class)->where('is_active', true)->orderBy('sort');
    }

    /**
     * Вращения колеса
     */
    public function spins(): HasMany
    {
        return $this->hasMany(Spin::class);
    }

    /**
     * Выигрыши колеса
     */
    public function wins(): HasMany
    {
        return $this->hasMany(Spin::class)->whereNotNull('prize_id');
    }
}
