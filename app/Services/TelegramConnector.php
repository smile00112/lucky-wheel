<?php

namespace App\Services;

use App\Contracts\PlatformConnector;
use App\Models\PlatformIntegration;
use App\Models\Spin;
use App\Models\TelegramUser;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\TelegramTextService;

class TelegramConnector implements PlatformConnector
{
    private const API_BASE_URL = 'https://api.telegram.org/bot';

    public function __construct(TelegramTextService $textService)
    {
        $this->textService = $textService;
    }

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

        $message = $this->formatSpinMessage($integration, $spin);

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
            Log::info('Telegram send message response', [
                'chat_id' => $userId,
                'message' => $message,
                'response' =>$response,
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

    private function formatSpinMessage(PlatformIntegration $integration, Spin $spin): string
    {
        $wheel = $spin->wheel;
        $prize = $spin->prize;

        $title = $this->textService->get($integration, 'spin_result_title', '🎡 <b>Результат вращения колеса</b>');
        $wheelLabel = $this->textService->get($integration, 'spin_result_wheel', 'Колесо:');

        $message = $this->replaceVariables($title, $wheel, $prize, $spin) . "\n\n";
        $message .= $this->replaceVariables($wheelLabel, $wheel, $prize, $spin) . " <b>{$wheel->name}</b>\n";

        if ($prize) {
            $prizeLabel = $this->textService->get($integration, 'spin_result_prize', '🎁 <b>Вы выиграли:');
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

