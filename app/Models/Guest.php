<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Guest extends Model
{
    protected $fillable = [
        'email',
        'phone',
        'name',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Вращения гостя
     */
    public function spins(): HasMany
    {
        return $this->hasMany(Spin::class);
    }

    /**
     * Выигрыши гостя
     */
    public function wins(): HasMany
    {
        return $this->hasMany(Spin::class)->whereNotNull('prize_id');
    }

    /**
     * IP-адреса гостя
     */
    public function ipAddresses(): HasMany
    {
        return $this->hasMany(GuestIpAddress::class);
    }

    /**
     * Telegram пользователь
     */
    public function telegramUser(): HasOne
    {
        return $this->hasOne(TelegramUser::class);
    }

    /**
     * Получить количество вращений для конкретного колеса
     */
    public function getSpinsCountForWheel(int $wheelId): int
    {
        return $this->spins()->where('wheel_id', $wheelId)->count();
    }

    /**
     * Scope для фильтрации по компании пользователя
     */
    public function scopeForCompany(Builder $query, ?int $companyId = null): Builder
    {
        if (!$companyId) {
            $user = auth()->user();
            if (!$user || $user->isAdmin()) {
                return $query;
            }
            $companyId = $user->company_id;
        }

        if (!$companyId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('spins.wheel.user', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        });
    }
}
