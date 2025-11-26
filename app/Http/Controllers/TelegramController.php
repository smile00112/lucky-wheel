<?php

namespace App\Http\Controllers;

use App\Models\Wheel;
use App\Models\Guest;
use App\Models\TelegramUser;
use App\Models\PlatformIntegration;
use App\Services\TelegramConnector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TelegramController extends Controller
{
    public function webapp(Request $request)
    {
        $wheelSlug = $request->query('wheel');

        if (!$wheelSlug) {
            abort(404, 'Wheel not specified');
        }

        $wheel = Wheel::where('slug', $wheelSlug)
            ->where('is_active', true)
            ->with('activePrizes')
            ->firstOrFail();

        // Проверка временных ограничений
        if ($wheel->starts_at && $wheel->starts_at->isFuture()) {
            abort(404, 'Wheel not available yet');
        }
        if ($wheel->ends_at && $wheel->ends_at->isPast()) {
            abort(404, 'Wheel has expired');
        }

        // Обработка guest_id из GET параметра
        $guest = null;
        $guestId = $request->query('guest_id');
        if ($guestId && is_numeric($guestId) && $guestId > 0) {
            $guest = Guest::find((int) $guestId);
        }

        return view('telegram.webapp', compact('wheel', 'guest'));
    }

    public function auth(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'initData' => 'required|string',
            'wheel_slug' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        $integration = PlatformIntegration::getByPlatform(PlatformIntegration::PLATFORM_TELEGRAM);

        if (!$integration || !$integration->is_active || !$integration->bot_token) {
            return response()->json([
                'error' => 'Telegram integration not configured',
            ], 503);
        }

        $connector = new TelegramConnector();

        // Валидация initData
        if (!$connector->validateInitData($request->initData, $integration->bot_token)) {
            return response()->json([
                'error' => 'Invalid init data',
            ], 401);
        }

        $authData = $connector->validateAuthData(['initData' => $request->initData]);

        if (!$authData) {
            return response()->json([
                'error' => 'Failed to parse auth data',
            ], 400);
        }

        // Найти или создать Telegram пользователя
        $telegramUser = TelegramUser::findByTelegramId($authData['telegram_id']);

        if (!$telegramUser) {
            // Создаем гостя
            $guest = Guest::create([
                'phone' => $authData['phone'],
                'name' => trim(($authData['first_name'] ?? '') . ' ' . ($authData['last_name'] ?? '')),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => [
                    'platform' => 'telegram',
                    'telegram_id' => $authData['telegram_id'],
                ],
            ]);

            // Создаем Telegram пользователя
            $telegramUser = TelegramUser::create([
                'guest_id' => $guest->id,
                'telegram_id' => $authData['telegram_id'],
                'username' => $authData['username'],
                'first_name' => $authData['first_name'],
                'last_name' => $authData['last_name'],
                'phone' => $authData['phone'],
                'metadata' => [
                    'init_data' => $request->initData,
                ],
            ]);
        } else {
            // Обновляем данные, если нужно
            $updateData = [];
            if ($authData['phone'] && !$telegramUser->phone) {
                $updateData['phone'] = $authData['phone'];
            }
            if ($authData['username'] && !$telegramUser->username) {
                $updateData['username'] = $authData['username'];
            }
            if (!empty($updateData)) {
                $telegramUser->update($updateData);
            }

            // Обновляем гостя
            $guest = $telegramUser->guest;
            if ($guest) {
                $guestUpdateData = [];
                if ($authData['phone'] && !$guest->phone) {
                    $guestUpdateData['phone'] = $authData['phone'];
                }
                if (!empty($guestUpdateData)) {
                    $guest->update($guestUpdateData);
                }
            }
        }

        return response()->json([
            'guest_id' => $telegramUser->guest_id,
            'telegram_id' => $telegramUser->telegram_id,
        ]);
    }

    public function spin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'wheel_slug' => 'required|string',
            'guest_id' => 'required|integer|exists:guests,id',
            'telegram_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        // Проверяем, что guest_id соответствует telegram_id
        $telegramUser = TelegramUser::where('guest_id', $request->guest_id)
            ->where('telegram_id', $request->telegram_id)
            ->first();

        if (!$telegramUser) {
            return response()->json([
                'error' => 'Invalid guest or telegram user',
            ], 403);
        }

        // Используем существующий метод spin из WidgetController
        $widgetController = new WidgetController();
        $spinResponse = $widgetController->spin($request);

        // Если вращение успешно, отправляем результат в Telegram
        if ($spinResponse->getStatusCode() === 200) {
            $spinData = json_decode($spinResponse->getContent(), true);

            if (isset($spinData['spin_id'])) {
                $integration = PlatformIntegration::getByPlatform(PlatformIntegration::PLATFORM_TELEGRAM);

                if ($integration && $integration->is_active) {
                    $spin = \App\Models\Spin::find($spinData['spin_id']);

                    //используется слушатель события вместо прямой отправки
//                    if ($spin) {
//                        $connector = new TelegramConnector();
//                        $connector->sendSpinResult($integration, $spin, (string)$request->telegram_id);
//                    }
                }
            }
        }

        return $spinResponse;
    }
}
