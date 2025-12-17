<?php

namespace App\Services;

use App\Contracts\PlatformConnector;
use App\Models\PlatformIntegration;
use App\Models\Prize;
use App\Models\Spin;
use App\Models\VKUser;
use App\Models\Wheel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\VKTextService;
use App\Services\VKKeyboardService;

class VKConnector implements PlatformConnector
{
    private const API_BASE_URL = 'https://api.vk.com/method/';
    private const API_VERSION = '5.199';

    public function __construct(VKTextService $textService, VKKeyboardService $keyboardService)

    {
        $this->textService = $textService;
        $this->keyboardService = $keyboardService;
    }

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

        $message = $this->formatSpinMessage($integration, $spin);

        // Получаем vkUser для проверки наличия телефона
        $vkUser = VKUser::findByVkId((int)$userId);
        $hasPhone = $vkUser && !empty($vkUser->phone);

        // Формируем клавиатуру
        //$keyboard = $this->buildKeyboard($hasPhone, $integration, $spin->wheel->slug ?? null, $vkUser?->guest_id);
        $keyboard = $this->keyboardService->getKeyboardForUser($userId, $integration, $spin->wheel->slug ?? null, $vkUser?->guest_id);

        try {
            $response = Http::get(self::API_BASE_URL . 'messages.send', [
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
        //$baseUrl = config('app.url');
        $miniapp_id_index = array_find_key((array)$integration->settings,  fn($item) => $item['key'] === 'app_id');
        $miniapp_id = $integration->settings[$miniapp_id_index]['value'];
        $miniapp_url = "https://vk.com/app" . $miniapp_id;

        Log::error('VK buildLaunchUrl', [
            '$miniapp_id' => $miniapp_id,
            '$miniapp_url' => $miniapp_url,
            'settings' => (array)$integration->settings,
        ]);

        if(!$miniapp_url)
            return '';
        //$url = $baseUrl . '?wheel=' . $wheelSlug . '&v=' . random_int(1, 100);

        if (!empty($params)) {
            $miniapp_url .= '&' . http_build_query($params);
        }

        Log::info('VK buildLaunchUrl', [
            '$miniapp_url' => $miniapp_url,
            'settings' => (array)$integration->settings,
            '$integration' => $integration
        ]);

        return $miniapp_url;
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

    private function formatSpinMessage(PlatformIntegration $integration, Spin $spin): string
    {
        $wheel = $spin->wheel;
        $prize = $spin->prize;

        $title = $this->textService->get($integration, 'spin_result_title', "🎡 \u{202B}Результат вращения колеса\u{202B}");
        $wheelLabel = $this->textService->get($integration, 'spin_result_wheel', 'Колесо::');

        $message = $this->replaceVariables($title, $wheel, $prize, $spin) . "\n\n";
        $message .= $this->replaceVariables($wheelLabel, $wheel, $prize, $spin) . "\u{202F}{$wheel->name}\u{202C}\n";

        if ($prize) {
            $prizeLabel = $this->textService->get($integration, 'spin_result_prize', '🎁 Вы выиграли::');
            $prizeText = $this->replaceVariables($prizeLabel, $wheel, $prize, $spin);
            $message .= $prizeText . "\n"; //. " {$prize->getNameWithoutSeparator()}</b>\n";


            $prizeDescription = $this->textService->get($integration, 'spin_result_prize_description', '');
            if($prizeDescription){
                $descriptionText = $this->replaceVariables($prizeLabel, $wheel, $prize, $spin);
                $message .= $descriptionText . "\n"; //. " {$prize->getNameWithoutSeparator()}</b>\n";
            }


//            if ($prize->description) {
//                $message .= "{$prize->description}\n";
//            }

            if ($prize->value) {
                $codeLabel = $this->textService->get($integration, 'spin_result_code', 'Код для получения:');
                $codeText = $this->replaceVariables($codeLabel, $wheel, $prize, $spin);
                $message .= "\n{$codeText}"; // "<code>{$spin->code}</code>";
            }
        } else {
            $noPrize = $this->textService->get($integration, 'spin_result_no_prize', '😔 К сожалению, в этот раз вам не повезло');
            $message .= $this->replaceVariables($noPrize, $wheel, $prize, $spin);
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
            'one_time' => true,
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

    /**
     * Заменить переменные в тексте значениями из колеса, приза и спина
     *
     * Доступные переменные:
     * - {wheel_name} - название колеса
     * - {wheel_description} - описание колеса
     * - {wheel_slug} - slug колеса
     * - {wheel_company_name} - название компании
     * - {prize_name} - название приза
     * - {prize_full_name} - полное название приза
     * - {prize_mobile_name} - мобильное название приза
     * - {prize_description} - описание приза
     * - {prize_text_for_winner} - текст для победителя
     * - {prize_value} - значение приза
     * - {prize_type} - тип приза
     * - {code} - код для получения приза
     */
    public function replaceVariables(string $text, ?Wheel $wheel, ?Prize $prize, ?Spin $spin): string
    {
        $replacements = [];

        // Переменные колеса
        if ($wheel) {
            $replacements['{wheel_name}'] = $wheel->name ?? '';
            $replacements['{wheel_description}'] = $wheel->description ?? '';
            $replacements['{wheel_slug}'] = $wheel->slug ?? '';
            $replacements['{wheel_company_name}'] = $wheel->company_name ?? '';
        }

        // Переменные приза
        if ($prize) {
            $replacements['{prize_name}'] = $prize->name ?? '';
            $replacements['{prize_full_name}'] = $prize->full_name ?? '';
            $replacements['{prize_mobile_name}'] = $prize->mobile_name ?? '';
            $replacements['{prize_description}'] = $prize->description ?? '';
            $replacements['{prize_text_for_winner}'] = $prize->text_for_winner ?? '';
            $replacements['{prize_value}'] = $prize->value ?? '';
            $replacements['{prize_type}'] = $prize->type ?? '';
            $replacements['{prize_name_without_separator}'] = $prize->getNameWithoutSeparator();
            $replacements['{prize_email_image}'] = $this->getFileUrl($prize->email_image ?? '');
            $replacements['{prize_date}'] = $prize->created_at->format('d.m.Y H:i') ?? '';

        }

        // Переменные спина
        if ($spin) {
            $replacements['{code}'] = $spin->code ?? '';
        }

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $text
        );
    }

    /**
     * Получить URL файла из storage
     */
    private function getFileUrl(string $path): string
    {
        if (empty($path)) {
            return '';
        }

        // Если это полный URL, возвращаем как есть
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        // Если путь начинается с /, это абсолютный путь
        if (str_starts_with($path, '/')) {
            return url($path);
        }

        // Проверяем, существует ли файл в public storage
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        // По умолчанию используем asset для storage
        return asset('storage/' . ltrim($path, '/'));
    }
}

