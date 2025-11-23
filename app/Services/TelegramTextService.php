<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PlatformIntegration;

class TelegramTextService
{
    public function get(?PlatformIntegration $integration, string $code, ?string $default = null): string
    {
        if ($integration && $integration->words_settings) {
            $settings = $integration->words_settings;
            $fullCode = 'telegram.' . $code;
            
            if (isset($settings[$fullCode]) && !empty($settings[$fullCode])) {
                return $settings[$fullCode];
            }
        }

        if ($default !== null) {
            return $default;
        }

        return __('telegram.' . $code, [], 'ru');
    }
}

