<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PlatformIntegration;

class VKTextService
{
    public function get(?PlatformIntegration $integration, string $code, ?string $default = null): string
    {
        if ($integration && $integration->words_settings) {
            $settings = $integration->words_settings;
            $fullCode = 'vk.' . $code;
            
            if (isset($settings[$fullCode]) && !empty($settings[$fullCode])) {
                return $settings[$fullCode];
            }
        }

        if ($default !== null) {
            return $default;
        }

        return __('vk.' . $code, [], 'ru');
    }
}

