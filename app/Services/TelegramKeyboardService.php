<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PlatformIntegration;
use App\Models\TelegramUser;
use Illuminate\Support\Facades\Log;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Types\ReplyKeyboardMarkup;

class TelegramKeyboardService
{
    private TelegramTextService $textService;

    public function __construct(TelegramTextService $textService)
    {
        $this->textService = $textService;
    }

    public function getKeyboardForUser(?int $telegramId, ?PlatformIntegration $integration = null): ReplyKeyboardMarkup
    {
        $hasPhone = $telegramId ? $this->hasPhoneNumber($telegramId) : false;

        Log::info('getKeyboardForUser', [
            'telegramId' => $telegramId,
            'hasPhone' => $hasPhone,
        ]);

        $sendPhoneText = $this->textService->get($integration, 'button_send_phone');
        $buttons = [
            [['text' => $sendPhoneText, 'request_contact' => true]]
        ];

        if ($hasPhone) {
            $spinText = $this->textService->get($integration, 'button_spin');
            $historyText = $this->textService->get($integration, 'button_history');
            $buttons[0][] = ['text' => $spinText];
            $buttons[] = [['text' => $historyText]];
        }

        return new ReplyKeyboardMarkup($buttons, true, true);
    }

    public function getWebAppInlineKeyboard(string $url, ?PlatformIntegration $integration = null, ?string $buttonText = null): InlineKeyboardMarkup
    {
        if ($buttonText === null) {
            $buttonText = $this->textService->get($integration, 'spin_button');
        }
        if($url){
            $data = [
                'text' => $buttonText,
                'web_app' => [
                    'url' => $url,
                ],
            ];
        }else{
            $data = [
                'text' => $buttonText,
            ];
        }

        return new InlineKeyboardMarkup([
            [
                $data
            ],
        ]);
    }

    public function hasPhoneNumber(int $telegramId): bool
    {
        $telegramUser = TelegramUser::findByTelegramId($telegramId);
        return $telegramUser && !empty($telegramUser->phone);
    }
}
