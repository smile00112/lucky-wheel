<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PlatformIntegration;
use Illuminate\Support\Facades\Log;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\BotCommand;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;

class TelegramBotService
{
    public function createBot(PlatformIntegration $integration): BotApi
    {
        return new BotApi($integration->bot_token);
    }

    public function sendMessage(
        BotApi $bot,
        int|string $chatId,
        string $text,
        $replyMarkup = null
    ): void {
        try {
            $bot->sendMessage($chatId, $text, 'HTML', false, null, $replyMarkup);
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram message', [
                'error' => $e->getMessage(),
                'chat_id' => $chatId,
            ]);
        }
    }

    public function sendWebAppButton(
        BotApi $bot,
        int|string $chatId,
        string $text,
        string|null $url,
        TelegramKeyboardService $keyboardService,
        ?PlatformIntegration $integration = null,
        ?string $buttonText = null
    ): void {
        try {
            $inlineKeyboard = $url ? $keyboardService->getWebAppInlineKeyboard($url, $integration, $buttonText) : null;

            $bot->sendMessage($chatId, $text, 'HTML', false, null, $inlineKeyboard);
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram message with web app button', [
                'error' => $e->getMessage(),
                'chat_id' => $chatId,
            ]);
        }
    }

    public function setBotCommands(BotApi $bot): void
    {
        try {
            $commands = [
                new BotCommand('start', 'Начать работу с ботом'),
                // Убираем команду /spin - она доступна через кнопку после расшаривания номера
                new BotCommand('spin', 'Крутить колесо'),
                new BotCommand('history', 'Посмотреть историю призов'),
            ];

            $bot->setMyCommands($commands);
        } catch (\Exception $e) {
            Log::error('Failed to set bot commands', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function answerCallbackQuery(BotApi $bot, string $queryId): void
    {
        try {
            $bot->answerCallbackQuery($queryId);
        } catch (\Exception $e) {
            Log::error('Failed to answer callback query', [
                'error' => $e->getMessage(),
                'query_id' => $queryId,
            ]);
        }
    }

    public function setMenuButton(
        BotApi $bot,
        string $text,
        string $url,
        ?int $chatId = null
    ): void {
        try {
            $payload = [
                'menu_button' => json_encode([
                    'type' => 'web_app',
                    'text' => $text,
                    'web_app' => ['url' => $url],
                ], JSON_THROW_ON_ERROR),
            ];

            if ($chatId) {
                $payload['chat_id'] = $chatId;
            }

            $bot->call('setChatMenuButton', $payload);
        } catch (\Throwable $e) {
            Log::error('Failed to set menu button', ['error' => $e->getMessage()]);
        }
    }

    public function removeMenuButton(BotApi $bot, ?int $chatId = null): void
    {
        try {
            $bot->call('setChatMenuButton', [
                'menu_button' => json_encode([
                    'type' => 'default',
                ], JSON_THROW_ON_ERROR),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to remove menu button', ['error' => $e->getMessage()]);
        }


        try {
            $payload = [
                'menu_button' => json_encode([
                    'type' => 'default',
                ], JSON_THROW_ON_ERROR),
            ];

            if ($chatId) {
                $payload['chat_id'] = $chatId;
            }

            $bot->call('setChatMenuButton', $payload);
        } catch (\Throwable $e) {
            Log::error('Failed to remove menu button', ['error' => $e->getMessage()]);
        }
    }
}

