<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PlatformIntegration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VKBotService
{
    private const API_BASE_URL = 'https://api.vk.com/method/';
    private const API_VERSION = '5.131';

    public function sendMessage(
        PlatformIntegration $integration,
        int|string $userId,
        string $text,
        ?array $keyboard = null
    ): bool {
        if (!$integration->bot_token) {
            return false;
        }

        try {
            $params = [
                'access_token' => $integration->bot_token,
                'user_id' => $userId,
                'message' => $text,
                'random_id' => random_int(0, PHP_INT_MAX),
                'v' => self::API_VERSION,
            ];

            if ($keyboard) {
                $params['keyboard'] = json_encode($keyboard);
            }

            $response = Http::post(self::API_BASE_URL . 'messages.send', $params);

            $result = $response->json();

            if (isset($result['response'])) {
                return true;
            }

            Log::error('VK send message failed', [
                'response' => $result,
                'user_id' => $userId,
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('VK send message error', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
            ]);

            return false;
        }
    }

    public function getUserInfo(PlatformIntegration $integration, int $userId): ?array
    {
        if (!$integration->bot_token) {
            return null;
        }

        try {
            $response = Http::post(self::API_BASE_URL . 'users.get', [
                'access_token' => $integration->bot_token,
                'user_ids' => $userId,
                'fields' => 'contacts, first_name, last_name',
                'v' => self::API_VERSION,
            ]);

            $result = $response->json();

            Log::info('get user info', [
                'result' =>$result,
                'user_id' => $userId,
            ]);

            if (isset($result['response']) && !empty($result['response'])) {
                return $result['response'][0];
            }

            return null;
        } catch (\Exception $e) {
            Log::error('VK get user info error', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
            ]);

            return null;
        }
    }

    public function buildKeyboard(array $buttons, bool $oneTime = false): array
    {
        return [
            'one_time' => $oneTime,
            'buttons' => $buttons,
        ];
    }
}

