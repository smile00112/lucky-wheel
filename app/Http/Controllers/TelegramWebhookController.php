<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PlatformIntegration;
use App\Models\TelegramUser;
use App\Services\TelegramBotService;
use App\Services\TelegramConnector;
use App\Services\TelegramKeyboardService;
use App\Services\TelegramMessageService;
use App\Services\TelegramTextService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use TelegramBot\Api\BotApi;

class TelegramWebhookController extends Controller
{
    private UserService $userService;
    private TelegramBotService $botService;
    private TelegramKeyboardService $keyboardService;
    private TelegramMessageService $messageService;
    private TelegramTextService $textService;

    public function __construct(
        UserService $userService,
        TelegramBotService $botService,
        TelegramKeyboardService $keyboardService,
        TelegramMessageService $messageService,
        TelegramTextService $textService
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
            Log::warning('Telegram webhook received but integration is not active', [
                'has_integration' => $integration !== null,
                'is_active' => $integration?->is_active,
            ]);
            return response()->json(['ok' => false], 503);
        }

        $data = $request->all();

        Log::info('Telegram webhook received', ['data' => $data]);

        if (!isset($data['message']) && !isset($data['callback_query'])) {
            return response()->json(['ok' => true]);
        }

        $bot = $this->botService->createBot($integration);
        $connector = app(TelegramConnector::class);

        // Устанавливаем меню команд при каждом запросе (если еще не установлено)
        $this->botService->setBotCommands($bot);

        try {
            if (isset($data['message'])) {
                $this->handleMessage($data['message'], $integration, $connector, $bot);
            }

            if (isset($data['callback_query'])) {
                $this->handleCallbackQuery($data['callback_query'], $integration, $connector, $bot);
            }
        } catch (\Exception $e) {
            Log::error('Error handling Telegram webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return response()->json(['ok' => true]);
    }

    private function handleMessage(
        array $message,
        PlatformIntegration $integration,
        TelegramConnector $connector,
        BotApi $bot
    ): void {
        $chatId = $message['chat']['id'] ?? null;
        $text = $message['text'] ?? '';
        $contact = $message['contact'] ?? null;
        $from = $message['from'] ?? null;
        $telegramId = $from['id'] ?? null;
        Log::info('handleMessage 1', [
            'message' => $message
        ]);
        if (!$chatId) {
            return;
        }

        // Обработка расшаренного контакта
        if ($contact) {
            $this->handleContact($message, $integration, $connector, $bot);
            return;
        }
        Log::info('handleMessage 2', [
            'message' => $message
        ]);
        // Обработка команды /start
        if ($text === '/start') {
            $this->handleStartCommand($chatId, $integration, $bot, $telegramId);
            return;
        }

        // Обработка команды /spin или текста из настроек кнопки "Крутить колесо"
        if ($this->matchesCommand($text, $integration, ['button_spin', 'spin_button'], ['/spin'])) {
            $this->handleSpinCommand($chatId, $integration, $connector, $bot, $telegramId);
            return;
        }

        // Обработка команды /history или текста из настроек кнопки "История"
        if ($this->matchesCommand($text, $integration, ['button_history'], ['/history'])) {
            $this->handleHistoryCommand($chatId, $message, $integration, $bot);
            return;
        }

        // Обработка кнопки "Отправить номер" из настроек
        if ($this->matchesCommand($text, $integration, ['button_send_phone'])) {
            $this->handleRequestContact($chatId, $integration, $bot, $telegramId);
            return;
        }

        // Обработка других сообщений
        $keyboard = $this->keyboardService->getKeyboardForUser($telegramId, $integration);
        $this->botService->sendMessage($bot, $chatId, $this->messageService->getUseStartCommand($integration), $keyboard);
    }

    private function handleStartCommand(
        int|string $chatId,
        PlatformIntegration $integration,
        BotApi $bot,
        ?int $telegramId = null
    ): void {
        // Устанавливаем меню команд
        $this->botService->setBotCommands($bot);

        $message = $this->messageService->getWelcomeMessage($integration);
        $keyboard = $this->keyboardService->getKeyboardForUser($telegramId, $integration);

        Log::info('handleStartCommand', [
            'bot' => $bot,
            'chatId' => $chatId,
            'message' => $message,
            'keyboard' => $keyboard,
        ]);

        $this->botService->sendMessage($bot, $chatId, $message, $keyboard);
        //чистим кнопку приложения
        $this->botService->removeMenuButton($bot, $chatId);
    }

    private function handleContact(
        array $message,
        PlatformIntegration $integration,
        TelegramConnector $connector,
        BotApi $bot
    ): void {
        $chatId = $message['chat']['id'] ?? null;
        $contact = $message['contact'] ?? null;
        $from = $message['from'] ?? null;

        Log::info('handleContact 1', [
            'message' => $message
        ]);

        if (!$chatId || !$contact || !$from) {
            return;
        }

        $telegramId = $from['id'] ?? null;
        $phoneNumber = $contact['phone_number'] ?? null;

        $wheel = $integration->wheel;

        if (!$telegramId || !$phoneNumber) {
            Log::error('handleContact error 1', [
                'message' => $message
            ]);
            $this->botService->sendMessage($bot, $chatId, $this->messageService->getContactError($integration));
            return;
        }

        try {
            // Проверяем, что контакт принадлежит пользователю
            $contactUserId = $contact['user_id'] ?? null;

            Log::info('handleContact 2', [
                'message' => $message
            ]);

            if ($contactUserId && (int)$contactUserId !== (int)$telegramId) {
                $this->botService->sendMessage($bot, $chatId, $this->messageService->getContactNotOwned($integration));

                Log::error('handleContact error 2', [
                    'contactUserId' => $contactUserId,
                    'telegramId' => $telegramId
                ]);

                return;
            }

            // Обрабатываем контакт через сервис
            $telegramUser = $this->userService->processTelegramContact($telegramId, [
                'phone_number' => $phoneNumber,
                'first_name' => $from['first_name'] ?? null,
                'last_name' => $from['last_name'] ?? null,
                'username' => $from['username'] ?? null,
            ]);

            // Отправляем сообщение об успешной регистрации и показываем постоянную клавиатуру
            $wheelSlug = $wheel->slug ?? null;
            Log::info('handleContact 3', [
                'wheel' => $wheel,
                'wheelSlug' => $wheelSlug,
            ]);

            if (!$wheelSlug) {
                Log::error('handleContact error 3', [
                    'wheelSlug' => $wheelSlug,
                ]);
                $keyboard = $this->keyboardService->getKeyboardForUser($telegramId, $integration);
                $this->botService->sendMessage($bot, $chatId, $this->messageService->getContactSavedButWheelNotConfigured($integration), $keyboard);
                return;
            }

            $keyboard = $this->keyboardService->getKeyboardForUser($telegramId, $integration);
            $this->botService->sendMessage($bot, $chatId, $this->messageService->getContactSavedMessage($integration), $keyboard);

            //добавляем кнопку на приложение
//            $webAppUrl = $connector->buildLaunchUrl($integration, $wheelSlug, ['guest_id' => $telegramUser->guest->id]);

//            if($webAppUrl)
//                $this->botService->setMenuButton($bot, 'Крутить колесо', $webAppUrl);
            $this->botService->removeMenuButton($bot);

        } catch (\Exception $e) {
            Log::error('Error processing contact', [
                'error' => $e->getMessage(),
                'telegram_id' => $telegramId,
                'phone' => $phoneNumber,
            ]);

            $this->botService->sendMessage($bot, $chatId, $this->messageService->getContactProcessingError($integration));

//            $webAppUrl = $connector->buildLaunchUrl($integration, $wheelSlug, ['guest_id' => $telegramUser->guest->id]);
//            Log::info('1111111 22', [
//
//                'webAppUrl' => $webAppUrl,
//
//            ]);

//            if($webAppUrl)
//                $this->botService->setMenuButton($bot, 'Крутить колесо', $webAppUrl);

        }
    }

    private function handleSpinCommand(
        int|string $chatId,
        PlatformIntegration $integration,
        TelegramConnector $connector,
        BotApi $bot,
        ?int $telegramId = null
    ): void {
        // Если telegramId не передан, используем chatId (в приватных чатах они совпадают)
        if (!$telegramId) {
            $telegramId = is_int($chatId) && $chatId > 0 ? $chatId : null;
        }

        $wheel = $integration->wheel;

        if (!$telegramId || !$this->keyboardService->hasPhoneNumber($telegramId)) {
            $keyboard = $this->keyboardService->getKeyboardForUser($telegramId, $integration);
            $this->botService->sendMessage($bot, $chatId, $this->messageService->getPhoneRequired($integration), $keyboard);
            return;
        }

        $wheelSlug = $wheel->slug ?? null;

        if (!$wheelSlug) {
            $keyboard = $this->keyboardService->getKeyboardForUser($telegramId, $integration);
            $this->botService->sendMessage($bot, $chatId, $this->messageService->getWheelNotConfigured($integration), $keyboard);
            return;
        }

        //к url вызова колеса добавляем id гостя
        $telegramUser = TelegramUser::findByTelegramId($telegramId);

        log::info('handleSpinCommand 1', [
            'telegram_id' => $telegramId,
            'wheel_slug' => $wheelSlug,
            '$chatId' => $chatId,
            '$telegramId' => $telegramId,
            '$telegramUser' => $telegramUser,
            '$telegramUser2' => $telegramUser->guest_id,


        ]) ;

        if (!$telegramUser || !$telegramUser->guest_id) {
            $keyboard = $this->keyboardService->getKeyboardForUser($telegramId, $integration);
            $this->botService->sendMessage($bot, $chatId, $this->messageService->getPhoneRequired($integration), $keyboard);
            return;
        }

        $webAppUrl = $connector->buildLaunchUrl($integration, $wheelSlug, ['guest_id' => $telegramUser->guest->id]);
        $this->botService->sendWebAppButton($bot, $chatId, $this->messageService->getSpinWelcomeMessage($integration), $webAppUrl, $this->keyboardService, $integration);

        $webAppUrl = $connector->buildLaunchUrl($integration, $wheelSlug, ['guest_id' => $telegramUser->guest->id]);

            log::info('$webAppUrl', ['$webAppUrl'=>$webAppUrl]);

//        if($webAppUrl)
//            $this->botService->setMenuButton($bot, 'Крутить колесо', $webAppUrl);
        $this->botService->removeMenuButton($bot);


    }

    private function handleHistoryCommand(
        int|string $chatId,
        array $message,
        PlatformIntegration $integration,
        BotApi $bot
    ): void {
        $from = $message['from'] ?? null;
        $telegramId = $from['id'] ?? null;

        if (!$telegramId) {
            $keyboard = $this->keyboardService->getKeyboardForUser(null, $integration);
            $this->botService->sendMessage($bot, $chatId, $this->messageService->getUserNotDetermined($integration), $keyboard);
            return;
        }

        $telegramUser = TelegramUser::findByTelegramId($telegramId);

        if (!$telegramUser || !$telegramUser->guest_id) {
            $keyboard = $this->keyboardService->getKeyboardForUser($telegramId, $integration);
            $this->botService->sendMessage($bot, $chatId, $this->messageService->getUserNotFound($integration), $keyboard);
            return;
        }

        $guest = $telegramUser->guest;
        $wins = $guest->wins()->with('prize')->orderBy('created_at', 'desc')->get();

        if ($wins->isEmpty()) {
            $keyboard = $this->keyboardService->getKeyboardForUser($telegramId, $integration);
            $this->botService->sendMessage($bot, $chatId, $this->messageService->getHistoryEmpty($integration), $keyboard);
            return;
        }

        $messageText = $this->messageService->getHistoryMessage($wins, $integration);
        $keyboard = $this->keyboardService->getKeyboardForUser($telegramId, $integration);
        $this->botService->sendMessage($bot, $chatId, $messageText, $keyboard);
        //$this->botService->removeMenuButton($bot);


    }

    private function handleRequestContact(
        int|string $chatId,
        PlatformIntegration $integration,
        BotApi $bot,
        ?int $telegramId = null
    ): void {
        $message = $this->messageService->getRequestContactMessage($integration);
        $keyboard = $this->keyboardService->getKeyboardForUser($telegramId, $integration);

        $this->botService->sendMessage($bot, $chatId, $message, $keyboard);
    }

    private function handleCallbackQuery(
        array $callbackQuery,
        PlatformIntegration $integration,
        TelegramConnector $connector,
        BotApi $bot
    ): void {
        $chatId = $callbackQuery['message']['chat']['id'] ?? null;
        $data = $callbackQuery['data'] ?? '';
        $queryId = $callbackQuery['id'] ?? null;
        $from = $callbackQuery['from'] ?? null;
        $telegramId = $from['id'] ?? null;

        if (!$chatId) {
            return;
        }

        try {
            // Отвечаем на callback query
            if ($queryId) {
                $this->botService->answerCallbackQuery($bot, $queryId);
            }

            if ($data === 'spin') {
                $wheel = $integration->wheel;

                // Проверяем наличие телефона перед показом кнопки
                if (!$telegramId || !$this->keyboardService->hasPhoneNumber($telegramId)) {
                    $keyboard = $this->keyboardService->getKeyboardForUser($telegramId, $integration);
                    $this->botService->sendMessage($bot, $chatId, $this->messageService->getPhoneRequired($integration), $keyboard);
                    return;
                }

                $wheelSlug = $wheel->slug ?? null;

                if (!$wheelSlug) {
                    $keyboard = $this->keyboardService->getKeyboardForUser($telegramId, $integration);
                    $this->botService->sendMessage($bot, $chatId, $this->messageService->getWheelNotConfigured($integration), $keyboard);
                    return;
                }

                //к url вызова колеса добавляем id гостя
                $telegramUser = TelegramUser::findByTelegramId($telegramId);

                if (!$telegramUser || !$telegramUser->guest_id) {
                    $keyboard = $this->keyboardService->getKeyboardForUser($telegramId, $integration);
                    $this->botService->sendMessage($bot, $chatId, $this->messageService->getPhoneRequired($integration), $keyboard);
                    return;
                }

                $webAppUrl = $connector->buildLaunchUrl($integration, $wheelSlug, ['guest_id' => $telegramUser->guest->id]);
                $this->botService->sendWebAppButton($bot, $chatId, $this->messageService->getSpinButtonMessage($integration), $webAppUrl, $this->keyboardService, $integration);
            }
        } catch (\Exception $e) {
            Log::error('Error handling callback query', [
                'error' => $e->getMessage(),
                'callback_query' => $callbackQuery,
            ]);
        }
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

        foreach ($staticVariants as $variant) {
            if ($variant !== '' && $text === $variant) {
                return true;
            }
        }

        foreach ($textCodes as $code) {
            $value = $this->textService->get($integration, $code);

            if ($value !== '' && $text === $value) {
                return true;
            }
        }

        return false;
    }
}
