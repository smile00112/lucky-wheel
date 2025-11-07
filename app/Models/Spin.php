<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Spin extends Model
{
    protected $fillable = [
        'wheel_id',
        'guest_id',
        'prize_id',
        'ip_address',
        'user_agent',
        'status',
        'claimed_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'claimed_at' => 'datetime',
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
}
