<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PrizeWon;
use App\Models\PlatformIntegration;
use App\Services\TelegramConnector;
use Illuminate\Support\Facades\Log;

class SendTelegramPrizeNotification
{
    public function __construct(
        private TelegramConnector $telegramConnector
    ) {
    }

    public function handle(PrizeWon $event): void
    {
        $spin = $event->spin;

        if (!$spin->isWin()) {
            return;
        }

        $guest = $spin->guest;
        if (!$guest) {
            return;
        }

        $telegramUser = $guest->telegramUser;
        if (!$telegramUser) {
            return;
        }

        $integration = PlatformIntegration::getByPlatform(PlatformIntegration::PLATFORM_TELEGRAM);
        if (!$integration || !$integration->is_active) {
            return;
        }

        try {
            $this->telegramConnector->sendSpinResult(
                $integration,
                $spin,
                (string) $telegramUser->telegram_id
            );
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram prize notification', [
                'error' => $e->getMessage(),
                'spin_id' => $spin->id,
                'telegram_user_id' => $telegramUser->telegram_id,
            ]);
        }
    }
}

