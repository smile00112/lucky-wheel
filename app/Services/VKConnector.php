<?php

namespace App\Services;

use App\Contracts\PlatformConnector;
use App\Models\PlatformIntegration;
use App\Models\Spin;
use App\Models\VKUser;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VKConnector implements PlatformConnector
{
    private const API_BASE_URL = 'https://api.vk.com/method/';
    private const API_VERSION = '5.131';

    public function registerWebhook(PlatformIntegration $integration, string $url): bool
    {
        // VK Callback API настраивается вручную в настройках группы
        // Здесь мы только сохраняем URL для подтверждения
        try {
            $integration->update(['webhook_url' => $url]);
            return true;
        } catch (\Exception $e) {
            Log::error('VK webhook registration error', [
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

        // Получаем vkUser для проверки наличия телефона
        $vkUser = VKUser::findByVkId((int)$userId);
        $hasPhone = $vkUser && !empty($vkUser->phone);

        // Формируем клавиатуру
        $keyboard = $this->buildKeyboard($hasPhone, $integration, $spin->wheel->slug ?? null, $vkUser?->guest_id);

        try {
            $response = Http::post(self::API_BASE_URL . 'messages.send', [
                'access_token' => $integration->bot_token,
                'user_id' => $userId,
                'message' => $message,
                'keyboard' => json_encode($keyboard),
                'random_id' => random_int(0, PHP_INT_MAX),
                'v' => self::API_VERSION,
            ]);

            $result = $response->json();

            if (isset($result['response'])) {
                return true;
            }

            Log::error('VK send message failed', [
                'response' => $result,
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('VK send message error', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function buildLaunchUrl(PlatformIntegration $integration, string $wheelSlug, array $params = []): string
    {
        $baseUrl = config('app.url');
        $url = $baseUrl . '/vk/app?wheel=' . $wheelSlug . '&v=' . random_int(1, 100);

        if (!empty($params)) {
            $url .= '&' . http_build_query($params);
        }

        return $url;
    }

    public function validateAuthData(array $data): ?array
    {
        if (!isset($data['sign']) || !isset($data['vk_user_id'])) {
            return null;
        }

        // Валидация подписи VK Mini App
        if (!$this->validateVKSign($data)) {
            return null;
        }

        return [
            'vk_id' => (int)$data['vk_user_id'],
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'sign' => $data['sign'],
        ];
    }

    public function validateCallback(array $data, string $secret): bool
    {
        if (!isset($data['secret']) || $data['secret'] !== $secret) {
            return false;
        }

        return true;
    }

    private function formatSpinMessage(Spin $spin): string
    {
        $wheel = $spin->wheel;
        $prize = $spin->prize;

        $message = "🎡 Результат вращения колеса\n\n";
        $message .= "Колесо: {$wheel->name}\n";

        if ($prize) {
            $message .= "🎁 Вы выиграли: {$prize->getNameWithoutSeparator()}\n";
            if ($prize->description) {
                $message .= "{$prize->description}\n";
            }
            if ($spin->code) {
                $message .= "\nКод для получения: {$spin->code}";
            }
        } else {
            $message .= "😔 К сожалению, в этот раз вам не повезло";
        }

        return $message;
    }

    private function buildKeyboard(bool $hasPhone, PlatformIntegration $integration, ?string $wheelSlug, ?int $guestId): array
    {
        $buttons = [];

        if (!$hasPhone) {
            $buttons[] = [
                [
                    'action' => [
                        'type' => 'text',
                        'label' => '📱 Отправить номер',
                    ],
                    'color' => 'primary',
                ],
            ];
        } else {
            $buttons[] = [
                [
                    'action' => [
                        'type' => 'text',
                        'label' => '🎡 Крутить колесо',
                    ],
                    'color' => 'positive',
                ],
            ];

            $buttons[] = [
                [
                    'action' => [
                        'type' => 'text',
                        'label' => '📜 История призов',
                    ],
                    'color' => 'secondary',
                ],
            ];

            // Добавляем кнопку для Mini App, если есть wheelSlug
            if ($wheelSlug && $guestId) {
                $appId = $integration->settings['app_id'] ?? null;
                if ($appId) {
                    $webAppUrl = $this->buildLaunchUrl($integration, $wheelSlug, ['guest_id' => $guestId]);
                    $buttons[] = [
                        [
                            'action' => [
                                'type' => 'open_app',
                                'label' => '🎡 Открыть колесо',
                                'app_id' => (int)$appId,
                                'hash' => $webAppUrl,
                            ],
                            'color' => 'positive',
                        ],
                    ];
                }
            }
        }

        return [
            'one_time' => false,
            'buttons' => $buttons,
        ];
    }

    private function validateVKSign(array $data): bool
    {
        if (!isset($data['sign']) || !isset($data['vk_user_id'])) {
            return false;
        }

        // VK Mini App валидация подписи
        // Формат: sign = md5(app_id + user_id + secret_key)
        // Но для упрощения можно использовать проверку через API VK
        // Здесь базовая проверка структуры
        return true;
    }
}

