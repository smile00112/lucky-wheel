<?php

namespace App\Services;

use App\Contracts\PlatformConnector;
use App\Models\PlatformIntegration;
use App\Models\Spin;
use App\Models\TelegramUser;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramConnector implements PlatformConnector
{
    private const API_BASE_URL = 'https://api.telegram.org/bot';

    public function registerWebhook(PlatformIntegration $integration, string $url): bool
    {
        if (!$integration->bot_token) {
            return false;
        }

        try {
            $response = Http::post(self::API_BASE_URL . $integration->bot_token . '/setWebhook', [
                'url' => $url,
            ]);

            if ($response->successful() && $response->json('ok')) {
                $integration->update(['webhook_url' => $url]);
                return true;
            }

            Log::error('Telegram webhook registration failed', [
                'response' => $response->json(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Telegram webhook registration error', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function sendSpinResult(PlatformIntegration $integration, Spin $spin, string $userId): bool
    {
        if (!$integration->bot_token) {
            return false;
        }

        $message = $this->formatSpinMessage($spin);

        // Получаем telegramUser для проверки наличия телефона
        $telegramUser = TelegramUser::findByTelegramId((int)$userId);
        $hasPhone = $telegramUser && !empty($telegramUser->phone);

        // Формируем постоянную клавиатуру
        $buttons = [
            [['text' => '📱 Отправить номер', 'request_contact' => true]]
        ];

        if ($hasPhone) {
            $buttons[0][] = ['text' => '🎡 Крутить колесо'];
            $buttons[] = [['text' => '📜 История призов']];
        }

        $replyMarkup = [
            'keyboard' => $buttons,
            'resize_keyboard' => true,
            'persistent' => true,
        ];

        try {
            $response = Http::post(self::API_BASE_URL . $integration->bot_token . '/sendMessage', [
                'chat_id' => $userId,
                'text' => $message,
                'parse_mode' => 'HTML',
                'reply_markup' => $replyMarkup,
            ]);

            return $response->successful() && $response->json('ok');
        } catch (\Exception $e) {
            Log::error('Telegram send message error', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function buildLaunchUrl(PlatformIntegration $integration, string $wheelSlug, array $params = []): string
    {
        $baseUrl = config('app.url');
        $url = $baseUrl . '/telegram/app?wheel=' . $wheelSlug . '&v=' . random_int(1, 100);

        if (!empty($params)) {
            $url .= '&' . http_build_query($params);
        }

        return $url;
    }

    public function validateAuthData(array $data): ?array
    {
        if (!isset($data['initData'])) {
            return null;
        }

        $initData = $data['initData'];
        parse_str($initData, $parsed);

        if (!isset($parsed['hash']) || !isset($parsed['user'])) {
            return null;
        }

        $user = json_decode($parsed['user'], true);
        if (!$user || !isset($user['id'])) {
            return null;
        }

        return [
            'telegram_id' => $user['id'],
            'username' => $user['username'] ?? null,
            'first_name' => $user['first_name'] ?? null,
            'last_name' => $user['last_name'] ?? null,
            'phone' => $parsed['phone_number'] ?? null,
            'init_data' => $initData,
        ];
    }

    public function validateInitData(string $initData, string $botToken): bool
    {
        parse_str($initData, $parsed);

        if (!isset($parsed['hash'])) {
            return false;
        }

        $hash = $parsed['hash'];
        unset($parsed['hash']);

        ksort($parsed);
        $dataCheckString = implode("\n", array_map(
            fn($key, $value) => "$key=$value",
            array_keys($parsed),
            $parsed
        ));

        $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
        $calculatedHash = bin2hex(hash_hmac('sha256', $dataCheckString, $secretKey, true));

        return hash_equals($calculatedHash, $hash);
    }

    private function formatSpinMessage(Spin $spin): string
    {
        $wheel = $spin->wheel;
        $prize = $spin->prize;

        $message = "🎡 <b>Результат вращения колеса</b>\n\n";
        $message .= "Колесо: <b>{$wheel->name}</b>\n";

        if ($prize) {
            $message .= "🎁 <b>Вы выиграли: {$prize->name}</b>\n";
            if ($prize->description) {
                $message .= "{$prize->description}\n";
            }
            if ($spin->code) {
                $message .= "\nКод для получения: <code>{$spin->code}</code>";
            }
        } else {
            $message .= "😔 К сожалению, в этот раз вам не повезло";
        }

        return $message;
    }
}

