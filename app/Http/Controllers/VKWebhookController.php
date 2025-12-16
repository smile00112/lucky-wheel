<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PlatformIntegration;
use App\Models\VKUser;
use App\Services\VKBotService;
use App\Services\VKConnector;
use App\Services\VKKeyboardService;
use App\Services\VKMessageService;
use App\Services\VKTextService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VKWebhookController extends Controller
{
    private UserService $userService;
    private VKBotService $botService;
    private VKKeyboardService $keyboardService;
    private VKMessageService $messageService;
    private VKTextService $textService;

    public function __construct(
        UserService $userService,
        VKBotService $botService,
        VKKeyboardService $keyboardService,
        VKMessageService $messageService,
        VKTextService $textService
    ) {
        $this->userService = $userService;
        $this->botService = $botService;
        $this->keyboardService = $keyboardService;
        $this->messageService = $messageService;
        $this->textService = $textService;
    }

    public function handle(PlatformIntegration $integration, Request $request)
    {
        if (!$integration || !$integration->is_active || !$integration->bot_token) {
            Log::warning('VK webhook received but integration is not active', [
                'has_integration' => $integration !== null,
                'is_active' => $integration?->is_active,
            ]);
            return response()->json(['ok' => false], 503);
        }

        $data = $request->all();

        Log::info('VK webhook received', ['data' => $data]);

        // Обработка подтверждения сервера
        if (isset($data['type']) && $data['type'] === 'confirmation') {
            $confirmationCode = array_find_key((array)$integration->settings,  fn($item) => $item['key'] === 'hook_verification_code');
            if ($confirmationCode !== false) {
                return response($integration->settings[$confirmationCode]['value'], 200)->header('Content-Type', 'text/plain');
            }
            return response('ok', 200)->header('Content-Type', 'text/plain');
        }

        // Валидация подписи Callback API
//        $secret = $integration->settings['secret'] ?? null;
//        if ($secret) {
//            $connector = new VKConnector();
//            if (!$connector->validateCallback($data, $secret)) {
//                Log::warning('VK webhook validation failed', ['data' => $data]);
//                return response()->json(['ok' => false], 403);
//            }
//        }

        // Обработка события message_new
        if (isset($data['type']) && $data['type'] === 'message_new') {
            try {
                $this->handleMessage($data['object']['message'] ?? [], $integration);
            } catch (\Exception $e) {
                Log::error('Error handling VK handleMessage', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        return response('ok' )->header('Content-Type', 'text/plain');
    }

    private function handleMessage(array $message, PlatformIntegration $integration): void
    {
        $userId = $message['from_id'] ?? null;
        $text = $message['text'] ?? '';
        $peerId = $message['peer_id'] ?? null;

        if (!$userId || !$peerId) {
            return;
        }

        // В группах peer_id может отличаться от user_id
        // Для личных сообщений они совпадают
        $vkId = $userId;

        Log::info('VK handleMessage', [
            'user_id' => $userId,
            'peer_id' => $peerId,
            'text' => $text,
        ]);

        // Обработка команды /Начать
        if ($text === '/start' || $text === 'Начать'|| $text === 'начать' || $text === 'start') {
            //Запрос данных пользователя
            $this->handleGuestSave($vkId, $integration);
            //Отправка приветствия
            $this->handleStartCommand($vkId, $integration);
            return;
        }

        // Обработка команды /spin или текста из настроек кнопки "Крутить колесо"
        if ($this->matchesCommand($text, $integration, ['button_spin', 'spin_button'], ['/spin', 'spin'])) {
            $this->handleSpinCommand($vkId, $integration);
            return;
        }

        // Обработка команды /history или текста из настроек кнопки "История"
        if ($this->matchesCommand($text, $integration, ['button_history'], ['/history', 'history'])) {
            $this->handleHistoryCommand($vkId, $integration);
            return;
        }

        // Обработка кнопки "Отправить номер" из настроек
        if ($this->matchesCommand($text, $integration, ['button_send_phone'])) {
            $this->handleRequestContact($vkId, $integration);
            return;
        }

        // Попытка извлечь телефон из текста сообщения
        $phone = $this->extractPhoneFromText($text);
        if ($phone) {
            $this->handlePhoneMessage($vkId, $phone, $integration);
            return;
        }

        // Обработка других сообщений
        $keyboard = $this->keyboardService->getKeyboardForUser($vkId, $integration);
        $this->botService->sendMessage($integration, $vkId, $this->messageService->getUseStartCommand($integration), $keyboard);
    }

    private function handleStartCommand(int $vkId, PlatformIntegration $integration): void
    {
        $message = $this->messageService->getWelcomeMessage($integration);
        $keyboard = $this->keyboardService->getKeyboardForUser($vkId, $integration);

        $this->botService->sendMessage($integration, $vkId, $message, $keyboard);
    }

    private function handleRequestContact(int $vkId, PlatformIntegration $integration): void
    {
        $message = $this->messageService->getRequestContactMessage($integration);
        $keyboard = $this->keyboardService->getKeyboardForUser($vkId, $integration);

        $this->botService->sendMessage($integration, $vkId, $message, $keyboard);
    }

    private function handlePhoneMessage(int $vkId, string $phone, PlatformIntegration $integration): void
    {
        $wheel = $integration->wheel;

        try {
            // Получаем информацию о пользователе из VK
            $userInfo = $this->botService->getUserInfo($integration, $vkId);
            //$ip = request()->get('HTTP_X_FORWARDED_FOR') ?? request()->ip();

            // Обрабатываем контакт через сервис
            $vkUser = $this->userService->processVKContact($vkId, [
                'phone_number' => $phone,
                'first_name' => $userInfo['first_name'] ?? null,
                'last_name' => $userInfo['last_name'] ?? null,
            ]);

            $wheelSlug = $wheel->slug ?? null;
            if (!$wheelSlug) {
                $keyboard = $this->keyboardService->getKeyboardForUser($vkId, $integration);
                $this->botService->sendMessage($integration, $vkId, $this->messageService->getContactSavedButWheelNotConfigured($integration), $keyboard);
                return;
            }

            $keyboard = $this->keyboardService->getKeyboardForUser($vkId, $integration, $wheelSlug, $vkUser->guest_id);
            $this->botService->sendMessage($integration, $vkId, $this->messageService->getContactSavedMessage($integration), $keyboard);
        } catch (\Exception $e) {
            Log::error('Error processing VK contact', [
                'error' => $e->getMessage(),
                'vk_id' => $vkId,
                'phone' => $phone,
            ]);

            $this->botService->sendMessage($integration, $vkId, $this->messageService->getContactError($integration));
        }
    }

    private function handleGuestSave(int $vkId, PlatformIntegration $integration): void
    {
        $wheel = $integration->wheel;

        try {
            // Получаем информацию о пользователе из VK
            $userInfo = $this->botService->getUserInfo($integration, $vkId);
            //$ip = request()->get('HTTP_X_FORWARDED_FOR') ?? request()->ip();

            // Обрабатываем контакт через сервис
            $vkUser = $this->userService->processVKContact($vkId, [
                'phone_number' => !empty($userInfo['contacts']['mobile_phone']) ? $userInfo['contacts']['mobile_phone'] : null,
                'first_name' => $userInfo['first_name'] ?? null,
                'last_name' => $userInfo['last_name'] ?? null,
                //'ip' => $ip
            ]);

            $wheelSlug = $wheel->slug ?? null;
            if (!$wheelSlug) {
                $keyboard = $this->keyboardService->getKeyboardForUser($vkId, $integration);
                $this->botService->sendMessage($integration, $vkId, $this->messageService->getContactSavedButWheelNotConfigured($integration), $keyboard);
                return;
            }

            //$keyboard = $this->keyboardService->getKeyboardForUser($vkId, $integration, $wheelSlug, $vkUser->guest_id);
            //$this->botService->sendMessage($integration, $vkId, $this->messageService->getContactSavedMessage($integration), $keyboard);
        } catch (\Exception $e) {
            Log::error('Error processing VK contact', [
                'error' => $e->getMessage(),
                'vk_id' => $vkId,
            ]);

            $this->botService->sendMessage($integration, $vkId, $this->messageService->getContactError($integration));
        }
    }

    private function handleSpinCommand(int $vkId, PlatformIntegration $integration): void
    {
        $wheel = $integration->wheel;

//        if (!$this->keyboardService->hasPhoneNumber($vkId)) {
//            $keyboard = $this->keyboardService->getKeyboardForUser($vkId, $integration);
//            $this->botService->sendMessage($integration, $vkId, $this->messageService->getPhoneRequired($integration), $keyboard);
//            return;
//        }

        $wheelSlug = $wheel->slug ?? null;

        if (!$wheelSlug) {
            $keyboard = $this->keyboardService->getKeyboardForUser($vkId, $integration);
            $this->botService->sendMessage($integration, $vkId, $this->messageService->getWheelNotConfigured($integration), $keyboard);
            return;
        }

        $vkUser = VKUser::findByVkId($vkId);

        if (!$vkUser || !$vkUser->guest_id) {
            $keyboard = $this->keyboardService->getKeyboardForUser($vkId, $integration);
            $this->botService->sendMessage($integration, $vkId, $this->messageService->getPhoneRequired($integration), $keyboard);
            return;
        }

        $connector = app(VKConnector::class);
        $webAppUrl = $connector->buildLaunchUrl($integration, $wheelSlug, ['guest_id' => $vkUser->guest->id]);

        $miniapp_id_index = array_find_key((array)$integration->settings,  fn($item) => $item['key'] === 'app_id');
        $appId = !empty($integration->settings[$miniapp_id_index]['value']) ? $integration->settings[$miniapp_id_index]['value'] : null;

        if ($appId) {

            $keyboard = [
                'one_time' => false,
                'buttons' => [
                    [
                        [
                            'action' => [
                                'type' => 'open_app',
                                'label' => $this->textService->get($integration, 'spin_button', '🎡 Крутить колесо'),
                                'app_id' => (int)$appId,
                                'hash' => $webAppUrl,
                            ],
                            //'color' => 'positive',
                        ],
                    ],
                ],
            ];
            // Log::info('1111111', [
            //     '$appId' => $appId,
            // 'keyboard' => $keyboard
            // ]);
        } else {

            // Log::info('222222', [
            //     '$appId' => $appId,

            // ]);
            $keyboard = $this->keyboardService->getKeyboardForUser($vkId, $integration, $wheelSlug, $vkUser->guest_id);
        }

        $this->botService->sendMessage($integration, $vkId, $this->messageService->getSpinWelcomeMessage($integration), $keyboard);
    }

    private function handleHistoryCommand(int $vkId, PlatformIntegration $integration): void
    {
        $vkUser = VKUser::findByVkId($vkId);

        if (!$vkUser || !$vkUser->guest_id) {
            $keyboard = $this->keyboardService->getKeyboardForUser($vkId, $integration);
            $this->botService->sendMessage($integration, $vkId, $this->messageService->getUserNotFound($integration), $keyboard);
            return;
        }

        $guest = $vkUser->guest;
        $wins = $guest->wins()->with('prize')->orderBy('created_at', 'desc')->get();

        if ($wins->isEmpty()) {
            $keyboard = $this->keyboardService->getKeyboardForUser($vkId, $integration);
            $this->botService->sendMessage($integration, $vkId, $this->messageService->getHistoryEmpty($integration), $keyboard);
            return;
        }

        $messageText = $this->messageService->getHistoryMessage($wins, $integration);
        $keyboard = $this->keyboardService->getKeyboardForUser($vkId, $integration);
        $this->botService->sendMessage($integration, $vkId, $messageText, $keyboard);
    }

    private function matchesCommand(
        ?string $text,
        PlatformIntegration $integration,
        array $textCodes = [],
        array $staticVariants = []
    ): bool {
        if ($text === null || $text === '') {
            return false;
        }

        $text = trim(mb_strtolower($text));

        foreach ($staticVariants as $variant) {
            if ($variant !== '' && mb_strtolower($variant) === $text) {
                return true;
            }
        }

        foreach ($textCodes as $code) {
            $value = $this->textService->get($integration, $code);

            if ($value !== '' && mb_strtolower($value) === $text) {
                return true;
            }
        }

        return false;
    }

    private function extractPhoneFromText(string $text): ?string
    {
        // Простой паттерн для извлечения телефона
        // Удаляем все символы кроме цифр и +
        $cleaned = preg_replace('/[^\d+]/', '', $text);

        // Проверяем, что это похоже на телефон (минимум 10 цифр)
        if (preg_match('/^\+?[0-9]{10,15}$/', $cleaned)) {
            // Если начинается с 8, заменяем на +7
            if (str_starts_with($cleaned, '8') && strlen($cleaned) === 11) {
                return '+7' . substr($cleaned, 1);
            }
            // Если начинается с 7 и нет +, добавляем +
            if (str_starts_with($cleaned, '7') && !str_starts_with($cleaned, '+7') && strlen($cleaned) === 11) {
                return '+' . $cleaned;
            }
            // Если 10 цифр без +, добавляем +7
            if (!str_starts_with($cleaned, '+') && strlen($cleaned) === 10) {
                return '+7' . $cleaned;
            }
            // Если уже с +, возвращаем как есть
            if (str_starts_with($cleaned, '+')) {
                return $cleaned;
            }
        }

        return null;
    }
}

