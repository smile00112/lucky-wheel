<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformIntegration extends Model
{
    public const PLATFORM_TELEGRAM = 'telegram';
    public const PLATFORM_VK = 'vk';
    public const PLATFORM_MAX = 'max';

    protected $fillable = [
        'platform',
        'wheel_id',
        'bot_token',
        'bot_username',
        'webhook_url',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    public static function getByPlatform(string $platform): ?self
    {
        return self::where('platform', $platform)->first();
    }

    /**
     * Связанное колесо
     */
    public function wheel(): BelongsTo
    {
        return $this->belongsTo(Wheel::class);
    }
}
