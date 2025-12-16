<?php

namespace App\Http\Controllers;

use App\Models\Wheel;
use App\Models\Guest;
use App\Models\VKUser;
use App\Models\PlatformIntegration;
use App\Services\VKConnector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class VKController extends Controller
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

        // Если guest_id не передан, но есть vk_user_id, находим или создаем Guest
        if (!$guest) {
            $vkUserId = $request->query('vk_user_id');
            if ($vkUserId && is_numeric($vkUserId) && $vkUserId > 0) {
                $vkUser = VKUser::findByVkId((int) $vkUserId);

                if ($vkUser && $vkUser->guest_id) {
                    $guest = $vkUser->guest;
                } else {
                    // Создаем нового Guest и VKUser, если их нет
                    $userService = app(\App\Services\UserService::class);
                    $guest = $userService->findOrCreateByVkId((int) $vkUserId, [
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'metadata' => [
                            'platform' => 'vk',
                            'vk_id' => (int) $vkUserId,
                        ],
                    ]);

                    // Убеждаемся, что VKUser создан и связан с Guest
                    if (!$vkUser) {
                        $userService->findOrCreateVKUser((int) $vkUserId, $guest, []);
                    } elseif ($vkUser->guest_id !== $guest->id) {
                        $vkUser->update(['guest_id' => $guest->id]);
                    }
                }
            }
        }

        return view('vk.webapp-v3', compact('wheel', 'guest'));
    }

    public function auth(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sign' => 'required|string',
            'vk_user_id' => 'required|integer',
            'wheel_slug' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        // Ищем интеграцию по платформе и slug колеса
        $integration = PlatformIntegration::getByPlatformAndWheelSlug(
            PlatformIntegration::PLATFORM_VK,
            $request->wheel_slug
        );

        if (!$integration || !$integration->is_active || !$integration->bot_token) {
            return response()->json([
                'error' => 'VK integration not configured for this wheel',
            ], 503);
        }

        $connector = app(VKConnector::class);

        // Валидация данных VK Mini App
        $authData = $connector->validateAuthData([
            'sign' => $request->sign,
            'vk_user_id' => $request->vk_user_id,
            'first_name' => $request->first_name ?? null,
            'last_name' => $request->last_name ?? null,
            'phone' => $request->phone ?? null,
        ]);

        if (!$authData) {
            return response()->json([
                'error' => 'Failed to validate auth data',
            ], 401);
        }

        // Найти или создать VK пользователя
        $vkUser = VKUser::findByVkId($authData['vk_id']);

        if (!$vkUser) {
            // Создаем гостя
            $guest = Guest::create([
                'phone' => $authData['phone'],
                'name' => trim(($authData['first_name'] ?? '') . ' ' . ($authData['last_name'] ?? '')),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => [
                    'platform' => 'vk',
                    'vk_id' => $authData['vk_id'],
                ],
            ]);

            // Создаем VK пользователя
            $vkUser = VKUser::create([
                'guest_id' => $guest->id,
                'vk_id' => $authData['vk_id'],
                'first_name' => $authData['first_name'],
                'last_name' => $authData['last_name'],
                'phone' => $authData['phone'],
                'metadata' => [
                    'sign' => $request->sign,
                ],
            ]);
        } else {
            // Обновляем данные, если нужно
            $updateData = [];
            if ($authData['phone'] && !$vkUser->phone) {
                $updateData['phone'] = $authData['phone'];
            }
            if ($authData['first_name'] && !$vkUser->first_name) {
                $updateData['first_name'] = $authData['first_name'];
            }
            if ($authData['last_name'] && !$vkUser->last_name) {
                $updateData['last_name'] = $authData['last_name'];
            }
            if (!empty($updateData)) {
                $vkUser->update($updateData);
            }

            // Обновляем гостя
            $guest = $vkUser->guest;
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
            'guest_id' => $vkUser->guest_id,
            'vk_id' => $vkUser->vk_id,
        ]);
    }

    public function spin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'wheel_slug' => 'required|string',
            'guest_id' => 'required|integer|exists:guests,id',
            'vk_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        // Проверяем, что guest_id соответствует vk_id
        $vkUser = VKUser::where('guest_id', $request->guest_id)
            ->where('vk_id', $request->vk_id)
            ->first();

        if (!$vkUser) {
            return response()->json([
                'error' => 'Invalid guest or vk user',
            ], 403);
        }

        // Используем существующий метод spin из WidgetController
        $widgetController = new WidgetController();
        $spinResponse = $widgetController->spin($request);

        // Если вращение успешно, отправляем результат в VK
        if ($spinResponse->getStatusCode() === 200) {
            $spinData = json_decode($spinResponse->getContent(), true);

            if (isset($spinData['spin_id'])) {
                // Ищем интеграцию по платформе и slug колеса
                $integration = PlatformIntegration::getByPlatformAndWheelSlug(
                    PlatformIntegration::PLATFORM_VK,
                    $request->wheel_slug
                );

                if ($integration && $integration->is_active) {
                    $spin = \App\Models\Spin::find($spinData['spin_id']);

                    // Используется слушатель события вместо прямой отправки
                    // if ($spin) {
                    //     $connector = new VKConnector();
                    //     $connector->sendSpinResult($integration, $spin, (string)$request->vk_id);
                    // }
                }
            }
        }

        return $spinResponse;
    }
}

