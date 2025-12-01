<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Random\RandomException;

class Spin extends Model
{
    protected $fillable = [
        'wheel_id',
        'guest_id',
        'prize_id',
        'code',
        'email_notification',
        'ip_address',
        'user_agent',
        'status',
        'claimed_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'claimed_at' => 'datetime',
        'email_notification' => 'boolean',
    ];

    /**
     * Колесо, на котором было вращение
     */
    public function wheel(): BelongsTo
    {
        return $this->belongsTo(Wheel::class);
    }

    /**
     * Гость, который крутил колесо
     */
    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    /**
     * Выигранный приз
     */
    public function prize(): BelongsTo
    {
        return $this->belongsTo(Prize::class);
    }

    /**
     * Отметить приз как полученный
     */
    public function markAsClaimed(): void
    {
        $this->update([
            'status' => 'claimed',
            'claimed_at' => now(),
        ]);
    }

    /**
     * Проверка, является ли вращение выигрышем
     */
    public function isWin(): bool
    {
        return $this->prize_id !== null;
    }

    /**
     * Генерация уникального шестизначного кода
     * @throws RandomException
     */
    public static function generateUniqueCode(): string
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $length = 6;
        do {
            $code= '';
            for ($i = 0; $i < $length; $i++) {
                $code .= $characters[random_int(0, $charactersLength - 1)];
            }
            //$code = str_pad((string) mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (self::where('code', $code)->exists());

        return $code;
    }

    /**
     * Отправить письмо о выигрыше
     */
    public function sendWinEmail(): bool
    {
        if (!$this->isWin() || !$this->guest->email) {
            return false;
        }

        try {
            $this->load('wheel.user');
            $user = $this->wheel->user;
            
            $mailConfigService = app(\App\Services\MailConfigService::class);
            $mailConfigService->configureForUser($user);
            
            \Illuminate\Support\Facades\Mail::to($this->guest->email)
                ->send(new \App\Mail\PrizeWinMail($this));

            $this->update(['email_notification' => true]);
            return true;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send prize email: ' . $e->getMessage());
            return false;
        }
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

        return $query->whereHas('wheel.user', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        });
    }
}
