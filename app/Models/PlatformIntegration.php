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
        'words_settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'words_settings' => 'array',
    ];

    /**
     * Получить интеграцию по платформе (старый метод для обратной совместимости)
     * @deprecated Используйте getByPlatformAndWheel() для поиска по колесу
     */
    public static function getByPlatform(string $platform): ?self
    {
        return self::where('platform', $platform)->first();
    }

    /**
     * Получить интеграцию по платформе и колесу
     */
    public static function getByPlatformAndWheel(string $platform, ?int $wheelId = null): ?self
    {
        $query = self::where('platform', $platform);
        
        if ($wheelId !== null) {
            $query->where('wheel_id', $wheelId);
        } else {
            $query->whereNull('wheel_id');
        }
        
        return $query->first();
    }

    /**
     * Получить интеграцию по платформе и slug колеса
     */
    public static function getByPlatformAndWheelSlug(string $platform, string $wheelSlug): ?self
    {
        return self::where('platform', $platform)
            ->whereHas('wheel', function ($query) use ($wheelSlug) {
                $query->where('slug', $wheelSlug);
            })
            ->first();
    }

    /**
     * Связанное колесо
     */
    public function wheel(): BelongsTo
    {
        return $this->belongsTo(Wheel::class);
    }

    /**
     * Получить настройки по умолчанию для Telegram
     */
    public static function getDefaultTelegramSettings(): array
    {
        $locale = app()->getLocale();

        return [
            'telegram.welcome' => __('telegram.welcome', [], $locale),
            'telegram.contact_saved' => __('telegram.contact_saved', [], $locale),
            'telegram.contact_saved_wheel_not_configured' => __('telegram.contact_saved_wheel_not_configured', [], $locale),
            'telegram.contact_error' => __('telegram.contact_error', [], $locale),
            'telegram.contact_not_owned' => __('telegram.contact_not_owned', [], $locale),
            'telegram.contact_processing_error' => __('telegram.contact_processing_error', [], $locale),
            'telegram.wheel_not_configured' => __('telegram.wheel_not_configured', [], $locale),
            'telegram.phone_required' => __('telegram.phone_required', [], $locale),
            'telegram.user_not_found' => __('telegram.user_not_found', [], $locale),
            'telegram.user_not_determined' => __('telegram.user_not_determined', [], $locale),
            'telegram.history_empty' => __('telegram.history_empty', [], $locale),
            'telegram.history_title' => __('telegram.history_title', [], $locale),
            'telegram.use_start_command' => __('telegram.use_start_command', [], $locale),
            'telegram.request_contact' => __('telegram.request_contact', [], $locale),
            'telegram.spin_welcome' => __('telegram.spin_welcome', [], $locale),
            'telegram.spin_button' => __('telegram.spin_button', [], $locale),
            'telegram.button_send_phone' => __('telegram.button_send_phone', [], $locale),
            'telegram.button_spin' => __('telegram.button_spin', [], $locale),
            'telegram.button_history' => __('telegram.button_history', [], $locale),
        ];
    }
}
