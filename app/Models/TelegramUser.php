<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramUser extends Model
{
    protected $fillable = [
        'guest_id',
        'telegram_id',
        'username',
        'first_name',
        'last_name',
        'phone',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public static function findByTelegramId(int $telegramId): ?self
    {
        return self::where('telegram_id', $telegramId)->first();
    }
}
