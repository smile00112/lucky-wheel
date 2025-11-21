<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PlatformIntegration;
use App\Models\TelegramUser;
use App\Services\TelegramConnector;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Types\ReplyKeyboardMarkup;
use TelegramBot\Api\Types\ReplyKeyboardRemove;
use TelegramBot\Api\Types\BotCommand;

class TelegramWebhookController extends Controller
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
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

        $bot = new BotApi($integration->bot_token);
        $connector = new TelegramConnector();

        // Устанавливаем меню команд при каждом запросе (если еще не установлено)
        $this->setBotCommands($bot);

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

        // Обработка команды /spin или текста "Крутить колесо"
        if ($text === '/spin' || $text === 'Крутить колесо' || $text === '🎡 Крутить колесо') {
            $this->handleSpinCommand($chatId, $integration, $connector, $bot, $telegramId);
            return;
        }

        // Обработка команды /history или текста "Посмотреть историю"
        if ($text === '/history' || $text === 'Посмотреть историю' || $text === '📜 Посмотреть историю' || $text === '📜 История призов') {
            $this->handleHistoryCommand($chatId, $message, $integration, $bot);
            return;
        }

        // Обработка кнопки "Отправить номер"
        if ($text === 'Отправить номер' || $text === '📱 Отправить номер') {
            $this->handleRequestContact($chatId, $integration, $bot, $telegramId);
            return;
        }

        // Обработка других сообщений
        $keyboard = $this->getKeyboardForUser($telegramId);
        $this->sendMessage($bot, $chatId, 'Используйте команду /start для начала работы.', $keyboard);
    }

    private function handleStartCommand(
        int|string $chatId,
        PlatformIntegration $integration,
        BotApi $bot,
        ?int $telegramId = null
    ): void {
        // Устанавливаем меню команд
        $this->setBotCommands($bot);

        $message = '👋 Добро пожаловать! Для продолжения работы поделитесь своим контактом.';

        $keyboard = $this->getKeyboardForUser($telegramId);

        try {
            $bot->sendMessage($chatId, $message, null, false, null, $keyboard);
        } catch (\Exception $e) {
            Log::error('Failed to send start message', [
                'error' => $e->getMessage(),
                'chat_id' => $chatId,
            ]);
        }
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

        if (!$telegramId || !$phoneNumber) {

            Log::error('handleContact error 1', [
                'message' => $message
            ]);
            $this->sendMessage($bot, $chatId, '❌ Не удалось получить контакт. Попробуйте еще раз.');
            return;
        }

        try {
            // Проверяем, что контакт принадлежит пользователю
            $contactUserId = $contact['user_id'] ?? null;

            Log::info('handleContact 2', [
                'message' => $message
            ]);

            if ($contactUserId && (int)$contactUserId !== (int)$telegramId) {
                $this->sendMessage($bot, $chatId, '❌ Пожалуйста, поделитесь своим контактом.');

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
            $wheelSlug = $integration->wheel->slug ?? null;
            Log::info('handleContact 3', [
                'wheel' => $integration->wheel,
                'wheelSlug' => $integration->wheel->slug,
            ]);
            if (!$wheelSlug) {

                Log::error('handleContact error 3', [
                    'wheelSlug' => $wheelSlug,
                ]);
                $keyboard = $this->getKeyboardForUser($telegramId);
                $this->sendMessage($bot, $chatId, '✅ Контакт сохранен! Колесо не настроено. Обратитесь к администратору.', $keyboard);
                return;
            }

            $keyboard = $this->getKeyboardForUser($telegramId);
            $this->sendMessage($bot, $chatId, '✅ Контакт сохранен! Теперь вы можете крутить колесо.', $keyboard);

        } catch (\Exception $e) {
            Log::error('Error processing contact', [
                'error' => $e->getMessage(),
                'telegram_id' => $telegramId,
                'phone' => $phoneNumber,
            ]);

            $this->sendMessage($bot, $chatId, '❌ Произошла ошибка при обработке контакта. Попробуйте еще раз.');
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

        if (!$telegramId || !$this->hasPhoneNumber($telegramId)) {
            $keyboard = $this->getKeyboardForUser($telegramId);
            $this->sendMessage($bot, $chatId, '❌ Для использования колеса необходимо поделиться контактом. Используйте команду /start.', $keyboard);
            return;
        }

        $wheelSlug = $integration->wheel->slug ?? null;

        if (!$wheelSlug) {
            $keyboard = $this->getKeyboardForUser($telegramId);
            $this->sendMessage($bot, $chatId, 'Колесо не настроено. Обратитесь к администратору.', $keyboard);
            return;
        }

        $webAppUrl = $connector->buildLaunchUrl($integration, $wheelSlug);

        Log::info('handleSpinCommand', [
            'webAppUrl' => $webAppUrl,
        ]);

        $this->sendWebAppButton($bot, $chatId, '🎡 Добро пожаловать! Нажмите кнопку, чтобы крутить колесо.', $webAppUrl);
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
            $keyboard = $this->getMainKeyboard();
            $this->sendMessage($bot, $chatId, '❌ Не удалось определить пользователя.', $keyboard);
            return;
        }

        $telegramUser = TelegramUser::findByTelegramId($telegramId);

        if (!$telegramUser || !$telegramUser->guest_id) {
            $keyboard = $this->getKeyboardForUser($telegramId);
            $this->sendMessage($bot, $chatId, '❌ Пользователь не найден. Пожалуйста, отправьте контакт через /start.', $keyboard);
            return;
        }

        $guest = $telegramUser->guest;
        $wins = $guest->wins()->with('prize')->orderBy('created_at', 'desc')->get();

        if ($wins->isEmpty()) {
            $keyboard = $this->getKeyboardForUser($telegramId);
            $this->sendMessage($bot, $chatId, '📜 У вас пока нет выигрышей.', $keyboard);
            return;
        }

        $messageText = "📜 <b>История ваших призов:</b>\n\n";

        foreach ($wins as $win) {
            $date = $win->created_at->format('d.m.Y H:i');
            $prizeName = $win->prize ? $win->prize->name : 'Неизвестный приз';
            $messageText .= "📅 {$date}\n🎁 {$prizeName}\n\n";
        }

        $keyboard = $this->getKeyboardForUser($telegramId);
        $this->sendMessage($bot, $chatId, $messageText, $keyboard);
    }

    private function handleRequestContact(
        int|string $chatId,
        PlatformIntegration $integration,
        BotApi $bot,
        ?int $telegramId = null
    ): void {
        $message = 'Пожалуйста, поделитесь своим контактом.';

        $keyboard = $this->getKeyboardForUser($telegramId);

        try {
            $bot->sendMessage($chatId, $message, null, false, null, $keyboard);
        } catch (\Exception $e) {
            Log::error('Failed to send contact request message', [
                'error' => $e->getMessage(),
                'chat_id' => $chatId,
            ]);
        }
    }

    private function getKeyboardForUser(?int $telegramId): ReplyKeyboardMarkup
    {
        $hasPhone = $telegramId ? $this->hasPhoneNumber($telegramId) : false;

        Log::info('getKeyboardForUser', [
            'telegramId' => $telegramId,
            'hasPhone' => $hasPhone,
        ]);

        $buttons = [
            [['text' => '📱 Отправить номер', 'request_contact' => true]]
        ];

        if ($hasPhone) {
            $buttons[0][] = ['text' => '🎡 Крутить колесо'];
            $buttons[] = [['text' => '📜 История призов']];
        }

        return new ReplyKeyboardMarkup($buttons, true, true);
    }

    private function hasPhoneNumber(int $telegramId): bool
    {
        $telegramUser = TelegramUser::findByTelegramId($telegramId);
        return $telegramUser && !empty($telegramUser->phone);
    }

    private function setBotCommands(BotApi $bot): void
    {
        try {
            $commands = [
                new BotCommand('start', 'Начать работу с ботом'),
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
                $bot->answerCallbackQuery($queryId);
            }

            if ($data === 'spin') {
                // Проверяем наличие телефона перед показом кнопки
                if (!$telegramId || !$this->hasPhoneNumber($telegramId)) {
                    $keyboard = $this->getKeyboardForUser($telegramId);
                    $this->sendMessage($bot, $chatId, '❌ Для использования колеса необходимо поделиться контактом. Используйте команду /start.', $keyboard);
                    return;
                }

                $wheelSlug = $integration->wheel->slug ?? null;

                if (!$wheelSlug) {
                    $keyboard = $this->getKeyboardForUser($telegramId);
                    $this->sendMessage($bot, $chatId, 'Колесо не настроено. Обратитесь к администратору.', $keyboard);
                    return;
                }

                $webAppUrl = $connector->buildLaunchUrl($integration, $wheelSlug);
                $this->sendWebAppButton($bot, $chatId, '🎡 Крутить колесо!', $webAppUrl);
            }
        } catch (\Exception $e) {
            Log::error('Error handling callback query', [
                'error' => $e->getMessage(),
                'callback_query' => $callbackQuery,
            ]);
        }
    }

    private function sendWebAppButton(
        BotApi $bot,
        int|string $chatId,
        string $text,
        string $url,
        $replyMarkup = null
    ): void {
        try {
            $inlineKeyboard = new InlineKeyboardMarkup([
                [
                    [
                        'text' => '🎡 Крутить колесо!',
                        'web_app' => [
                            'url' => $url,
                        ],
                    ],
                ],
            ]);

            // Отправляем сообщение с inline кнопкой
            // Постоянная клавиатура (replyMarkup) остается видимой автоматически
            $bot->sendMessage($chatId, $text, 'HTML', false, null, $inlineKeyboard);
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram message with web app button', [
                'error' => $e->getMessage(),
                'chat_id' => $chatId,
            ]);
        }
    }

    private function sendMessage(
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
}
