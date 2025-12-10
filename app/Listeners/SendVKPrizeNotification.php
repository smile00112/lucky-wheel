<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PrizeWon;
use App\Models\PlatformIntegration;
use App\Services\VKConnector;
use Illuminate\Support\Facades\Log;

class SendVKPrizeNotification
{
    public function __construct(
        private VKConnector $vkConnector
    ) {
    }

    public function handle(PrizeWon $event): void
    {
        $spin = $event->spin;

        Log::info('SendVKPrizeNotification handle called', [
            'spin_id' => $event->spin->id,
        ]);

        if (!$spin->isWin()) {
            return;
        }

        $guest = $spin->guest;
        if (!$guest) {
            return;
        }

        $vkUser = $guest->vkUser;
        if (!$vkUser) {
            return;
        }

        // Ищем интеграцию по платформе и колесу из spin
        $wheel = $spin->wheel;
        if (!$wheel) {
            return;
        }

        $integration = PlatformIntegration::getByPlatformAndWheel(
            PlatformIntegration::PLATFORM_VK,
            $wheel->id
        );
        
        if (!$integration || !$integration->is_active) {
            return;
        }

        // Защита от дублирования
        $cacheKey = "vk_notification_sent_{$spin->id}";
        if (cache()->has($cacheKey)) {
            Log::warning('VK notification already sent for spin', ['spin_id' => $spin->id]);
            return;
        }

        try {
            $this->vkConnector->sendSpinResult(
                $integration,
                $spin,
                (string) $vkUser->vk_id
            );
            
            cache()->put($cacheKey, true, now()->addHours(24));
        } catch (\Exception $e) {
            Log::error('Failed to send VK prize notification', [
                'error' => $e->getMessage(),
                'spin_id' => $spin->id,
                'vk_user_id' => $vkUser->vk_id,
            ]);
        }
    }
}

