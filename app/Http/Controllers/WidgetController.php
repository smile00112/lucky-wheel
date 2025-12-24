<?php

namespace App\Http\Controllers;

use App\Events\PrizeWon;
use App\Models\VKUser;
use App\Models\Wheel;
use App\Models\Guest;
use App\Models\GuestIpAddress;
use App\Models\Spin;
use App\Models\Prize;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Dompdf\Dompdf;
use Dompdf\Options;

class WidgetController extends Controller
{
    /**
     * Обработка OPTIONS запросов для CORS
     */
    public function options()
    {
        return response('', 200);
    }

    /**
     * Отобразить первое доступное колесо
     */
    public function show(Request $request)
    {
        // Получаем первое активное колесо
        $wheel = Wheel::where('is_active', true)
            ->with('activePrizes')
            ->first();

        // Если нет активного колеса, берем первое доступное
        if (!$wheel) {
            $wheel = Wheel::with('activePrizes')->first();
        }

        // Если нет колеса вообще, возвращаем ошибку
        if (!$wheel) {
            abort(404, 'No wheel found');
        }

        // Проверка временных ограничений
        $now = now();
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

        return view('widget.wheel-v2', compact('wheel', 'guest'));
    }

    /**
     * Отобразить виджет для iframe
     */
    public function embed(string $slug, Request $request)
    {
        $wheel = Wheel::where('slug', $slug)
            ->where('is_active', true)
            ->with('activePrizes')
            ->firstOrFail();

        // Проверка временных ограничений
        $now = now();
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

        // Проверяем, нужен ли только контент (без HTML структуры)
        $contentOnly = request()->query('content_only', false);

//        if ($contentOnly) {
//            return view('widget.wheel-content', compact('wheel'));
//        }
//
//        return view('widget.wheel', compact('wheel'));

        return view('widget.wheel-page', compact('wheel', 'guest'));
    }

    /**
     * Отобразить виджет для iframe (новая версия v2)
     */
    public function embedV2(string $slug, Request $request)
    {
        $wheel = Wheel::where('slug', $slug)
            ->where('is_active', true)
            ->with('activePrizes')
            ->firstOrFail();

        // Проверка временных ограничений
        $now = now();
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

        return view('widget.wheel-v2', compact('wheel', 'guest'));
    }

    /**
     * Отобразить виджет для iframe (версия v3)
     */
    public function embedV3(string $slug, Request $request)
    {
        $wheel = Wheel::where('slug', $slug)
            ->where('is_active', true)
            ->with('activePrizes')
            ->firstOrFail();

        // Проверка временных ограничений
        $now = now();
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

        // Проверяем, нужен ли только контент (без HTML структуры)
        $contentOnly = request()->query('content_only', false);

        if ($contentOnly) {
            return view('widget.wheel-v3', compact('wheel', 'guest'));
        }

        return view('widget.wheel-v3-page', compact('wheel', 'guest'));
    }

    /**
     * Отобразить виджет для VK (версия v3)
     */
    public function vkV3(string $slug, Request $request)
    {
        //TODO - перенести в VKController
        $wheel = Wheel::where('slug', $slug)
            ->where('is_active', true)
            ->with('activePrizes')
            ->firstOrFail();

        // Проверка временных ограничений
        $now = now();
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

        // Проверяем, нужен ли только контент (без HTML структуры)
//        $contentOnly = request()->query('content_only', false);
//
//        if ($contentOnly) {
//            return view('widget.wheel-v3', compact('wheel', 'guest'));
//        }

        return view('widget.wheel-v3-page-vk', compact('wheel', 'guest'));
    }


    /**
     * Получить данные колеса (JSON API)
     */
    public function getWheel(string $slug)
    {
        $wheel = Wheel::where('slug', $slug)
            ->where('is_active', true)
            ->with('activePrizes')
            ->firstOrFail();

        // Проверка временных ограничений
        $now = now();
        if ($wheel->starts_at && $wheel->starts_at->isFuture()) {
            return response()->json([
                'error' => 'Колесо призов отключено',
            ], 404);
        }
        if ($wheel->ends_at && $wheel->ends_at->isPast()) {
            return response()->json([
                'error' => 'Колесо призов отключено',//Wheel has expired
            ], 404);
        }

        $prizes = $wheel->activePrizes->map(function ($prize) {
            $imageUrl =  $emailImageUrl = null;
            if ($prize->image) {
                // Используем прокси-роут для всех изображений
                // Кодируем путь в base64url для безопасной передачи
                $encodedPath = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($prize->image));
                $imageUrl = route('image.proxy', ['path' => $encodedPath]);
            }
            if ($prize->email_image) {
                // Если изображение - это полный URL, используем как есть
                if (filter_var($prize->email_image, FILTER_VALIDATE_URL)) {
                    $emailImageUrl = $prize->email_image;
                } elseif (str_starts_with($prize->email_image, '/')) {
                    // Если путь начинается с /, это абсолютный путь
                    $emailImageUrl = url($prize->email_image);
                } elseif (Storage::disk('public')->exists($prize->email_image)) {
                    // Если файл в public storage
                    $emailImageUrl = Storage::disk('public')->url($prize->email_image);
                } else {
                    // По умолчанию используем asset для storage
                    $emailImageUrl = asset('storage/' . ltrim($prize->email_image, '/'));
                }

            }

            return [
                'id' => $prize->id,
                'name' => $prize->name,
                'full_name' => $prize->full_name,
                'mobile_name' => $prize->mobile_name,
                'sector_view' => $prize->sector_view ?? 'text_with_image',
                'description' => $prize->description,
                'color' => $prize->color,
                'use_gradient' => (bool) $prize->use_gradient,
                'gradient_start' => $prize->gradient_start,
                'gradient_end' => $prize->gradient_end,
                'text_color' => $prize->text_color,
                'font_size' => $prize->font_size,
                'mobile_font_size' => $prize->mobile_font_size,
                'probability' => (float) $prize->probability,
                'type' => $prize->type,
                'value' => $prize->value,
                'image' => $imageUrl,
                'email_image' => $emailImageUrl,
            ];
        });

        // Получаем тексты из settings с fallback на значения по умолчанию
        $defaultTexts = [
            'loading_text' => 'Загрузка...',
            'spin_button_text' => 'Крутить колесо!',
            'spin_button_blocked_text' => 'Вы уже выиграли сегодня. Попробуйте завтра!',
            'won_prize_label' => 'Выиграно сегодня:',
            'win_notification_title' => 'Ваш подарок',
            'win_notification_win_text' => 'Скопируйте промокод или покажите QR-код на ресепшене',
            'copy_code_button_title' => 'Копировать код',
            'code_not_specified' => 'Код не указан',
            'download_pdf_text' => 'Скачать сертификат PDF',
            'form_description' => 'Для получения приза на почту заполните данные:',
            'form_name_placeholder' => 'Ваше имя',
            'form_email_placeholder' => 'Email',
            'form_phone_placeholder' => '+7 (XXX) XXX-XX-XX',
            'form_submit_text' => 'Отправить приз',
            'form_submit_loading' => 'Отправка...',
            'form_submit_success' => '✓ Приз отправлен!',
            'form_submit_error' => 'Приз уже получен',
            'form_success_message' => '✓ Данные сохранены! Приз будет отправлен на указанную почту.',
            'prize_image_alt' => 'Приз',
            'spins_info_format' => 'Вращений: {count} / {limit}',
            'spins_limit_format' => 'Лимит вращений: {limit}',
            'error_init_guest' => 'Ошибка инициализации: не удалось создать гостя',
            'error_init' => 'Ошибка инициализации:',
            'error_no_prizes' => 'Нет доступных призов',
            'error_load_data' => 'Ошибка загрузки данных:',
            'error_spin' => 'При розыгрыше произошла ошибка! Обратитесь в поддержку сервиса.',
            'error_general' => 'Ошибка:',
            'error_send' => 'Ошибка при отправке',
            'error_copy_code' => 'Не удалось скопировать код. Пожалуйста, скопируйте вручную:',
            'wheel_default_name' => 'Колесо Фортуны',
            'win_notification_message_dop' => 'Скопируйте промокод или покажите QR-код на ресепшене',
            'win_notification_before_contact_form' => 'Заполните форму, чтобы получить приз',
        ];

        $settings = $wheel->settings ?? [];
        $texts = array_merge($defaultTexts, $settings);

        $imageUrl = null;
        if ($wheel->image) {
            $imageUrl = Storage::disk('public')->url($wheel->image);
        }

        return response()->json([
            'id' => $wheel->id,
            'name' => $wheel->name,
            'description' => $wheel->description,
            'slug' => $wheel->slug,
            'spins_limit' => $wheel->spins_limit,
            'force_data_collection' => (bool) $wheel->force_data_collection,
            'image' => $imageUrl,
            'prizes' => $prizes,
            'texts' => $texts,
        ]);
    }

    /**
     * Создать или получить гостя
     */
    public function createOrGetGuest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'message' => 'Ошибка валидации данных',
                'messages' => $validator->errors(),
            ], 422);
        }

        // Попытка найти существующего гостя
        $guest = null;

        if ($request->has('email') && $request->email) {
            $guest = Guest::where('email', $request->email)->first();
        } elseif ($request->has('phone') && $request->phone) {
            $guest = Guest::where('phone', $request->phone)->first();
        }

        // Если гость не найден, создаем нового
        if (!$guest) {
            $guest = Guest::create([
                'email' => $request->email,
                'phone' => $request->phone,
                'name' => $request->name,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => [
                    'referer' => $request->header('Referer'),
                    'origin' => $request->header('Origin'),
                ],
            ]);
        }

        return response()->json([
            'id' => $guest->id,
            'email' => $guest->email,
            'phone' => $guest->phone,
            'name' => $guest->name,
        ]);
    }

    /**
     * Выполнить вращение колеса
     */
    public function spin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'wheel_slug' => 'required|string',
            'guest_id' => 'required|integer|exists:guests,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'message' => 'Ошибка валидации данных',
                'messages' => $validator->errors(),
            ], 422);
        }

        $wheel = Wheel::where('slug', $request->wheel_slug)
            ->where('is_active', true)
            ->firstOrFail();

        $guest = Guest::findOrFail($request->guest_id);

        // Проверка временных ограничений
        $now = now();
        if ($wheel->starts_at && $wheel->starts_at->isFuture()) {
            return response()->json([
                'error' => 'Wheel not available yet',
            ], 403);
        }
        if ($wheel->ends_at && $wheel->ends_at->isPast()) {
            return response()->json([
                'error' => 'Wheel has expired',
            ], 403);
        }

        // Проверка лимита вращений для гостя
        $guestSpinsCount = $guest->getSpinsCountForWheel($wheel->id);
        if ($wheel->spins_limit && $guestSpinsCount >= $wheel->spins_limit) {
            return response()->json([
                'error' => 'Spin limit reached',
                'spins_count' => $guestSpinsCount,
                'spins_limit' => $wheel->spins_limit,
            ], 403);
        }

        // Получаем IP адрес клиента
        $clientIp = $request->ip();

        // Проверка выигрыша по IP адресу (для предотвращения обхода через инкогнито)
        $lastWinByIp = null;
//        if ($clientIp) {
//            // Ищем последний выигрыш с этого IP адреса для этого колеса
//            $lastWinByIp = Spin::where('wheel_id', $wheel->id)
//                ->whereNotNull('prize_id')
//                ->where(function ($query) use ($clientIp) {
//                    // Проверяем IP в основной таблице spins
//                    $query->where('ip_address', $clientIp)
//                        // Или проверяем через связанные IP адреса гостей
//                        ->orWhereHas('guest', function ($q) use ($clientIp) {
//                            $q->where('ip_address', $clientIp)
//                                ->orWhereHas('ipAddresses', function ($ipq) use ($clientIp) {
//                                    $ipq->where('ip_address', $clientIp);
//                                });
//                        });
//                })
//                ->orderBy('created_at', 'desc')
//                ->first();
//        }

        // Проверка выигрыша по guest_id
        $lastWin = Spin::where('guest_id', $guest->id)
            ->where('wheel_id', $wheel->id)
            ->whereNotNull('prize_id')
            ->orderBy('created_at', 'desc')
            ->first();

        // Используем более ранний выигрыш (по IP или по guest_id)
        $lastWin = $lastWinByIp && (!$lastWin || $lastWinByIp->created_at->gt($lastWin->created_at))
            ? $lastWinByIp
            : $lastWin;

        if ($lastWin) {
            $canSpinAgain = false;

            if ($wheel->refresh_hour) {
                // Парсим refresh_hour (формат: "HH:mm")
                $refreshTimeParts = explode(':', $wheel->refresh_hour);
                $refreshHour = (int) $refreshTimeParts[0];
                $refreshMinute = (int) ($refreshTimeParts[1] ?? 0);

                $now = now();
                $lastWinTime = $lastWin->created_at;

                // Вычисляем время обновления для дня последнего выигрыша
                $refreshTimeOnWinDay = $lastWinTime->copy()->setTime($refreshHour, $refreshMinute);

                // Определяем следующее доступное время для вращения
                if ($lastWinTime->lt($refreshTimeOnWinDay)) {
                    // Если выигрыш был до refresh_hour в день выигрыша, можно крутить после refresh_hour того же дня
                    $nextAllowedTime = $refreshTimeOnWinDay;
                } else {
                    // Если выигрыш был после refresh_hour в день выигрыша, можно крутить после refresh_hour следующего дня
                    $nextAllowedTime = $lastWinTime->copy()->addDay()->setTime($refreshHour, $refreshMinute);
                }

                $canSpinAgain = $now->gte($nextAllowedTime);
            } else {
                // Если refresh_hour не установлен, используем старую логику (блокируем до полуночи)
                $canSpinAgain = !$lastWin->created_at->isToday();
            }

            if (!$canSpinAgain) {
                $prize = $lastWin->prize;
                $message = $wheel->refresh_hour
                    ? "Вы уже выиграли. Попробуйте снова после {$wheel->refresh_hour}!"
                    : 'Вы уже выиграли сегодня. Попробуйте завтра!';

                return response()->json([
                    'error' => 'Already won',
                    'message' => $message,
                    'today_win' => [
                        'spin_id' => $lastWin->id, // ID спина для отправки приза
                        'prize' => [
                            'id' => $prize->id,
                            'name' => $prize->name,
                            'text_for_winner' => $prize->text_for_winner,
                            'type' => $prize->type,
                            'value' => $prize->value,
                        ],
                        'code' =>$prize->value, // $lastWin->code, // Код из spin
                    ],
                ], 403);
            }
        }

        try {
            DB::beginTransaction();

            // Выбор приза с учетом вероятностей и лимитов
            if ($wheel->probability_type === 'weighted') {
                $prize = $this->selectWeightedPrize($wheel, $guest->id);
            } else {
                $prize = $this->selectRandomPrize($wheel, $guest->id);
            }


            if(!$prize){
                //призов не осталось, выключаем колесо
                $wheel->update(['is_active' => false]);
                
                // Отправка уведомления на email из настроек
                $this->sendWheelDisabledNotification($wheel);
                
                DB::commit();
                return response()->json([
                    'error' => 'Spin failed',
                    'message' => 'Ошибка розыгрыша, попробуйте позже',
                ], 500);
            }

            // Создание записи о вращении
            $spinData = [
                'wheel_id' => $wheel->id,
                'guest_id' => $guest->id,
                'prize_id' => $prize ? $prize->id : null,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => 'completed', //completed - начальный статус
                'metadata' => [
                    'referer' => $request->header('Referer'),
                    'origin' => $request->header('Origin'),
                ],
            ];

            // Генерация кода при выигрыше
            if ($prize) {
                $spinData['code'] = Spin::generateUniqueCode();
            }

            $spin = Spin::create($spinData);

            // Увеличение счетчика полученных призов, если приз был выигран
            if ($prize) {
                $prize->incrementUsed();
            }

            DB::commit();

            $guestHasData = !empty($guest->email) && !empty($guest->name);// && !empty($guest->phone)

            return response()->json([
                'spin_id' => $spin->id,
                'prize' => $prize ? [
                    'id' => $prize->id,
                    'name' => $prize->name,
                    'full_name' => $prize->full_name,
                    'description' => $prize->description,
                    'text_for_winner' => $prize->text_for_winner,
                    'type' => $prize->type,
                    'email_image' => $prize->email_image,
                    'value' => $prize->value,
                ] : null,
                'code' => $prize->value, //$spin->code, // Код из spin, а не value из prize
                'has_prize' => $prize !== null,
                'spins_count' => $guestSpinsCount + 1,
                'spins_limit' => $wheel->spins_limit,
                'guest_has_data' => $guestHasData,
                'win_guest_data' => $guest

            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Spin error: ' . $e->getMessage());

            return response()->json([
                'error' => 'Spin failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Завершение вращения колеса (вызывается после окончания анимации)
     * Отправляет событие PrizeWon
     */
    public function completeSpin(Request $request, int $spinId)
    {
        try {
            $spin = Spin::with(['prize', 'guest'])->findOrFail($spinId);

            // Проверяем, что у спина есть приз
            if (!$spin->prize) {
                return response()->json([
                    'error' => 'No prize for this spin',
                ], 400);
            }

            // Отправка события о выигрыше приза
            event(new PrizeWon($spin));

            return response()->json([
                'success' => true,
                'message' => 'Spin completed',
            ]);
        } catch (\Exception $e) {
            Log::error('Complete spin error: ' . $e->getMessage());

            return response()->json([
                'error' => 'Complete spin failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить сегодняшний выигрыш гостя
     */
    public function getTodayWin(Request $request, string $slug)
    {
        $guestId = $request->query('guest_id');

        if (!$guestId) {
            return response()->json([
                'error' => 'Guest ID required',
            ], 422);
        }

        $wheel = Wheel::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        // Получаем IP адрес клиента
        $clientIp = $request->ip();

        // Проверка выигрыша по IP адресу (для предотвращения обхода через инкогнито)
        $lastWinByIp = null;
//        if ($clientIp) {
//            // Ищем последний выигрыш с этого IP адреса для этого колеса
//            $lastWinByIp = Spin::where('wheel_id', $wheel->id)
//                ->whereNotNull('prize_id')
//                ->where(function ($query) use ($clientIp) {
//                    // Проверяем IP в основной таблице spins
//                    $query->where('ip_address', $clientIp)
//                        // Или проверяем через связанные IP адреса гостей
//                        ->orWhereHas('guest', function ($q) use ($clientIp) {
//                            $q->where('ip_address', $clientIp)
//                                ->orWhereHas('ipAddresses', function ($ipq) use ($clientIp) {
//                                    $ipq->where('ip_address', $clientIp);
//                                });
//                        });
//                })
//                ->orderBy('created_at', 'desc')
//                ->with(['prize', 'guest'])
//                ->first();
//        }

        // Проверка выигрыша по guest_id
        $lastWin = Spin::where('guest_id', $guestId)
            ->where('wheel_id', $wheel->id)
            ->whereNotNull('prize_id')
            ->orderBy('created_at', 'desc')
            ->with(['prize', 'guest'])
            ->first();

        // Используем более ранний выигрыш (по IP или по guest_id)
        $lastWin = $lastWinByIp && (!$lastWin || $lastWinByIp->created_at->gt($lastWin->created_at))
            ? $lastWinByIp
            : $lastWin;

        if ($lastWin && $lastWin->prize) {
            // Проверяем, можно ли считать это "активным" выигрышем
            $isActiveWin = false;

            if ($wheel->refresh_hour) {
                // Парсим refresh_hour (формат: "HH:mm")
                $refreshTimeParts = explode(':', $wheel->refresh_hour);
                $refreshHour = (int) $refreshTimeParts[0];
                $refreshMinute = (int) ($refreshTimeParts[1] ?? 0);

                $now = now();
                $lastWinTime = $lastWin->created_at;

                // Вычисляем время обновления для дня последнего выигрыша
                $refreshTimeOnWinDay = $lastWinTime->copy()->setTime($refreshHour, $refreshMinute);

                // Определяем следующее доступное время для вращения
                if ($lastWinTime->lt($refreshTimeOnWinDay)) {
                    // Если выигрыш был до refresh_hour в день выигрыша, можно крутить после refresh_hour того же дня
                    $nextAllowedTime = $refreshTimeOnWinDay;
                } else {
                    // Если выигрыш был после refresh_hour в день выигрыша, можно крутить после refresh_hour следующего дня
                    $nextAllowedTime = $lastWinTime->copy()->addDay()->setTime($refreshHour, $refreshMinute);
                }

                // Выигрыш активен, если еще не прошло время для следующего вращения
                $isActiveWin = $now->lt($nextAllowedTime);
            } else {
                // Если refresh_hour не установлен, используем старую логику
                $isActiveWin = $lastWin->created_at->isToday();
            }

            if ($isActiveWin) {
                // Проверяем, заполнены ли данные у гостя, который выиграл
                $winGuest = $lastWin->guest;
                $hasData = false;
                if ($winGuest) {
                    $hasData = !empty($winGuest->email) && !empty($winGuest->name);  /*&& !empty($winGuest->phone) */
                }

                return response()->json([
                    'has_win' => true,
                    'spin_id' => $lastWin->id, // ID спина для отправки приза
                    'prize' => [
                        'id' => $lastWin->prize->id,
                        'name' => $lastWin->prize->name,
                        'full_name' => $lastWin->prize->full_name,
                        'text_for_winner' => $lastWin->prize->text_for_winner,
                        'type' => $lastWin->prize->type,
                        'email_image' => $lastWin->prize->email_image,
                        'value' => $lastWin->prize->value,
                    ],
                    'code' => $lastWin->value, //$lastWin->code, // Код из spin
                    'win_date' => $lastWin->created_at->toIso8601String(),
                    'guest_has_data' => $hasData, // Флаг, заполнены ли данные у гостя, который выиграл
                    'win_guest_id' => $winGuest ? $winGuest->id : null, // ID гостя, который выиграл
                    'win_guest_data' => $winGuest
                ]);
            }
        }

        return response()->json([
            'has_win' => false,
        ]);
    }

    /**
     * Получить историю вращений гостя
     */
    public function getGuestSpins(Request $request, int $guestId)
    {
        $wheelSlug = $request->query('wheel_slug');

        $query = Spin::where('guest_id', $guestId)
            ->with(['prize', 'wheel']);

        if ($wheelSlug) {
            $query->whereHas('wheel', function ($q) use ($wheelSlug) {
                $q->where('slug', $wheelSlug);
            });
        }

        $spins = $query->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($spin) {
                return [
                    'id' => $spin->id,
                    'wheel_name' => $spin->wheel->name,
                    'prize' => $spin->prize ? [
                        'name' => $spin->prize->name,
                        'type' => $spin->prize->type,
                    ] : null,
                    'code' => $spin->prize->value, //$spin->code, // Код из spin
                    'has_prize' => $spin->isWin(),
                    'status' => $spin->status,
                    'created_at' => $spin->created_at->toISOString(),
                ];
            });

        return response()->json([
            'spins' => $spins,
        ]);
    }

    /**
     * Выбрать приз на основе вероятностей и лимитов
     */
    private function selectWeightedPrize(Wheel $wheel, int $guestId): ?Prize
    {
        // Получаем все активные призы
        $allPrizes = $wheel->activePrizes()->get();

        // Фильтруем призы по всем доступным лимитам
        $availablePrizes = $allPrizes->filter(function ($prize) use ($guestId) {
            return $prize->isFullyAvailable($guestId);
        });

        if ($availablePrizes->isEmpty()) {
            return null;
        }

        // Нормализация вероятностей только для доступных призов
        $totalProbability = $availablePrizes->sum('probability');

        if ($totalProbability <= 0) {
            // Если вероятности не заданы, возвращаем null (нет приза)
            return null;
        }

        // Выбор случайного приза на основе вероятностей
        $random = mt_rand(1, 100) / 100.0;
        $cumulative = 0;

        foreach ($availablePrizes as $prize) {
            $probability = (float) $prize->probability / $totalProbability;
            $cumulative += $probability;

            if ($random <= $cumulative) {
                return $prize;
            }
        }

        // Если по какой-то причине приз не выбран, возвращаем случайный доступный приз
        return $availablePrizes->random();
    }

    /**
     * Выбрать случайный приз с равной вероятностью для всех доступных призов
     */
    private function selectRandomPrize(Wheel $wheel, int $guestId): ?Prize
    {
        // Получаем все активные призы
        $allPrizes = $wheel->activePrizes()->get();

        // Фильтруем призы по всем доступным лимитам
        $availablePrizes = $allPrizes->filter(function ($prize) use ($guestId) {
            return $prize->isFullyAvailable($guestId);
        });

        if ($availablePrizes->isEmpty()) {
            return null;
        }

        // Выбор случайного приза с равной вероятностью
        return $availablePrizes->random();
    }

    /**
     * Получить информацию о госте (проверить, заполнены ли данные)
     */
    public function getGuestInfo(Request $request, int $guestId)
    {
        try {
            $guest = Guest::find($guestId);
        }
        catch (\Exception $e) {
            return response()->json([
                'error' => 'Guest not found',
            ], 404);
        }

        if (!$guest) {
            return response()->json([
                'error' => 'Guest not found',
            ], 404);
        }

        Log::info('getGuestInfo ', [
            'id' => $guest->id,
            'email' => $guest->email,
            'phone' => $guest->phone,
            'name' => $guest->name,
        ]);

        // Проверяем, заполнены ли основные данные
        $hasData = !empty($guest->email) || /*!empty($guest->phone) ||*/ !empty($guest->name);

        return response()->json([
            'id' => $guest->id,
            'has_data' => $hasData,
            'email' => $guest->email,
            'phone' => $guest->phone,
            'name' => $guest->name,
        ]);
    }

    /**
     * Обновить данные гостя
     */
    public function updateGuest(Request $request, int $guestId)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'message' => 'Ошибка валидации данных',
                'messages' => $validator->errors(),
            ], 422);
        }

        $guest = Guest::find($guestId);
        if (!$guest) {
            return response()->json([
                'error' => 'Guest not found',
            ], 404);
        }

        $updateData = [];
        if ($request->has('name')) {
            $updateData['name'] = $request->input('name');
        }
        if ($request->has('email')) {
            $updateData['email'] = $request->input('email');
        }
        if ($request->has('phone')) {
            $updateData['phone'] = $request->input('phone');
        }

        if (!empty($updateData)) {
            $guest->update($updateData);
        }

        return response()->json([
            'id' => $guest->id,
            'email' => $guest->email,
            'phone' => $guest->phone,
            'name' => $guest->name,
        ]);
    }

    /**
     * Сохранить данные гостя и отправить приз
     */
    public function claimPrize(Request $request, int $guestId)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'wheel_slug' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'message' => 'Ошибка валидации данных',
                'messages' => $validator->errors(),
            ], 422);
        }

        // Находим текущего гостя
        $currentGuest = Guest::find($guestId);
        if (!$currentGuest) {
            return response()->json([
                'error' => 'Guest not found',
            ], 404);
        }

        $email = $request->input('email');
        $phone = $request->input('phone');
        $name = $request->input('name');
        $currentIp = $request->ip();

        // Получаем колесо для проверки
        $wheel = Wheel::where('slug', $request->input('wheel_slug'))
            ->where('is_active', true)
            ->first();

        if (!$wheel) {
            return response()->json([
                'error' => 'Wheel not found',
            ], 404);
        }

        // Ищем существующего гостя по email или phone
        $existingGuest = null;
        if ($email) {
            $existingGuest = Guest::where('email', $email)->where('id', '!=', $guestId)->first();
        }
//        if (!$existingGuest && $phone) {
//            $existingGuest = Guest::where('phone', $phone)->where('id', '!=', $guestId)->first();
//        }

        if ($existingGuest) {
            // Проверяем, получал ли этот гость приз сегодня
            $lastWin = Spin::where('guest_id', $existingGuest->id)
                ->where('wheel_id', $wheel->id)
                ->whereNotNull('prize_id')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($lastWin) {
                $canClaimAgain = false;

                if ($wheel->refresh_hour) {
                    // Парсим refresh_hour (формат: "HH:mm")
                    $refreshTimeParts = explode(':', $wheel->refresh_hour);
                    $refreshHour = (int) $refreshTimeParts[0];
                    $refreshMinute = (int) ($refreshTimeParts[1] ?? 0);

                    $now = now();
                    $lastWinTime = $lastWin->created_at;

                    // Вычисляем время обновления для дня последнего выигрыша
                    $refreshTimeOnWinDay = $lastWinTime->copy()->setTime($refreshHour, $refreshMinute);

                    // Определяем следующее доступное время для получения приза
                    if ($lastWinTime->lt($refreshTimeOnWinDay)) {
                        // Если выигрыш был до refresh_hour в день выигрыша, можно получить после refresh_hour того же дня
                        $nextAllowedTime = $refreshTimeOnWinDay;
                    } else {
                        // Если выигрыш был после refresh_hour в день выигрыша, можно получить после refresh_hour следующего дня
                        $nextAllowedTime = $lastWinTime->copy()->addDay()->setTime($refreshHour, $refreshMinute);
                    }

                    $canClaimAgain = $now->gte($nextAllowedTime);
                } else {
                    // Если refresh_hour не установлен, используем проверку на сегодня
                    $canClaimAgain = !$lastWin->created_at->isToday();
                }

                if (!$canClaimAgain) {
                    $message = $wheel->refresh_hour
                        ? "Приз уже был получен сегодня. Попробуйте снова после {$wheel->refresh_hour}!"
                        : 'Приз уже был получен сегодня. Попробуйте завтра!';

                    return response()->json([
                        'error' => 'Prize already claimed today',
                        'message' => $message,
                    ], 403);
                }
            }
            // Найден существующий гость - добавляем новый IP к нему
            // Проверяем, нет ли уже такого IP
            $ipExists = $existingGuest->ipAddresses()
                ->where('ip_address', $currentIp)
                ->exists();

            if (!$ipExists) {
                $existingGuest->ipAddresses()->create([
                    'ip_address' => $currentIp,
                    'user_agent' => $request->userAgent(),
                    'metadata' => [
                        'referer' => $request->header('Referer'),
                        'origin' => $request->header('Origin'),
                        'merged_from_guest_id' => $guestId,
                    ],
                ]);
            }

            // Обновляем данные существующего гостя, если они не заполнены
            $updateData = [];
            if ($email && !$existingGuest->email) {
                $updateData['email'] = $email;
            }
            if ($phone && !$existingGuest->phone) {
                $updateData['phone'] = $phone;
            }
            if ($name && !$existingGuest->name) {
                $updateData['name'] = $name;
            }
            if (!empty($updateData)) {
                $existingGuest->update($updateData);
            }

            // Переносим все вращения от текущего гостя к существующему
            Spin::where('guest_id', $guestId)->update(['guest_id' => $existingGuest->id]);

            // Переносим все IP-адреса от текущего гостя к существующему
            // (проверяем, чтобы не было дубликатов)
            $currentGuest->ipAddresses()->each(function ($ipAddress) use ($existingGuest) {
                $ipExists = $existingGuest->ipAddresses()
                    ->where('ip_address', $ipAddress->ip_address)
                    ->exists();

                if (!$ipExists) {
                    $ipAddress->update(['guest_id' => $existingGuest->id]);
                } else {
                    $ipAddress->delete();
                }
            });

            // Удаляем текущего гостя (так как он был объединен с существующим)
            $currentGuest->delete();

            // Отправка письма о выигрыше, если у гостя есть email
            try {
                $last_guest_spin = $existingGuest->spins()->latest('id')->first();
                //$spin = Spin::where('guest_id', $guestId)->update(['guest_id' => $existingGuest->id]);
                $last_guest_spin->sendWinEmail();
            } catch (\Exception $e) {
                Log::error('Failed to send win email: ' . $e->getMessage());
                // Не прерываем выполнение, если письмо не отправилось
            }

            return response()->json([
                'success' => true,
                'message' => 'Prize claim processed successfully',
                'guest_id' => $existingGuest->id,
            ]);
        } else {
            // Существующий гость не найден - обновляем текущего гостя
            $updateData = [];
            if ($email) {
                $updateData['email'] = $email;
            }
            if ($phone) {
                $updateData['phone'] = $phone;
            }
            if ($name) {
                $updateData['name'] = $name;
            }

            // Обновляем IP адрес, если он изменился
            if ($currentIp && $currentGuest->ip_address !== $currentIp) {
                // Проверяем, нет ли уже такого IP в связанной таблице
                $ipExists = $currentGuest->ipAddresses()
                    ->where('ip_address', $currentIp)
                    ->exists();

                if (!$ipExists) {
                    $currentGuest->ipAddresses()->create([
                        'ip_address' => $currentIp,
                        'user_agent' => $request->userAgent(),
                        'metadata' => [
                            'referer' => $request->header('Referer'),
                            'origin' => $request->header('Origin'),
                        ],
                    ]);
                }

                // Обновляем основной IP адрес
                $updateData['ip_address'] = $currentIp;
            }

            if (!empty($updateData)) {
                $currentGuest->update($updateData);
            }

            // Отправка письма о выигрыше, если у гостя есть email
            try {
                $last_guest_spin = $currentGuest->spins()->latest('id')->first();
                //$spin = Spin::where('guest_id', $guestId)->update(['guest_id' => $existingGuest->id]);
                if($last_guest_spin)
                    $last_guest_spin->sendWinEmail();
            } catch (\Exception $e) {
                Log::error('Failed to send win email: ' . $e->getMessage());
                // Не прерываем выполнение, если письмо не отправилось
            }

            return response()->json([
                'success' => true,
                'message' => 'Prize claim processed successfully',
                'guest_id' => $currentGuest->id,
            ]);
        }
    }

    /**
     * Отправить приз на почту (без ввода данных, только по spin_id)
     */
    public function sendPrizeEmail(Request $request, int $spinId)
    {
        $spin = Spin::with(['prize', 'guest'])->find($spinId);

        if (!$spin) {
            return response()->json([
                'error' => 'Spin not found',
            ], 404);
        }

        if (!$spin->isWin()) {
            return response()->json([
                'error' => 'This spin is not a win',
            ], 400);
        }

        if (!$spin->guest) {
            return response()->json([
                'error' => 'Guest not found for this spin',
            ], 404);
        }

        if (!$spin->guest->email) {
            return response()->json([
                'error' => 'Guest email is not set',
            ], 400);
        }

        // Проверяем, не был ли уже отправлен приз сегодня
        $wheel = $spin->wheel;
        if ($wheel) {
            $lastWin = Spin::where('guest_id', $spin->guest_id)
                ->where('wheel_id', $wheel->id)
                ->whereNotNull('prize_id')
                ->where('id', '!=', $spinId)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($lastWin) {
                $canClaimAgain = false;

                if ($wheel->refresh_hour) {
                    $refreshTimeParts = explode(':', $wheel->refresh_hour);
                    $refreshHour = (int) $refreshTimeParts[0];
                    $refreshMinute = (int) ($refreshTimeParts[1] ?? 0);

                    $now = now();
                    $lastWinTime = $lastWin->created_at;
                    $refreshTimeOnWinDay = $lastWinTime->copy()->setTime($refreshHour, $refreshMinute);

                    if ($lastWinTime->lt($refreshTimeOnWinDay)) {
                        $nextAllowedTime = $refreshTimeOnWinDay;
                    } else {
                        $nextAllowedTime = $lastWinTime->copy()->addDay()->setTime($refreshHour, $refreshMinute);
                    }

                    $canClaimAgain = $now->gte($nextAllowedTime);
                } else {
                    $canClaimAgain = !$lastWin->created_at->isToday();
                }

                if (!$canClaimAgain) {
                    $message = $wheel->refresh_hour
                        ? "Приз уже был получен сегодня. Попробуйте снова после {$wheel->refresh_hour}!"
                        : 'Приз уже был получен сегодня. Попробуйте завтра!';

                    return response()->json([
                        'error' => 'Prize already claimed today',
                        'message' => $message,
                    ], 403);
                }
            }
        }

        // Отправляем письмо
        try {
            $result = $spin->sendWinEmail();

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Prize email sent successfully',
                ]);
            } else {
                return response()->json([
                    'error' => 'Failed to send email',
                    'message' => 'Email notification could not be sent',
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send prize email: ' . $e->getMessage());

            return response()->json([
                'error' => 'Failed to send email',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Скачать PDF сертификат выигрыша
     */
    public function downloadWinPdf(Request $request, int $spinId)
    {
        $spin = Spin::with(['prize', 'guest', 'wheel'])->find($spinId);

        if (!$spin) {
            abort(404, 'Spin not found');
        }

        if (!$spin->isWin()) {
            abort(400, 'This spin is not a win');
        }

        if (!$spin->prize) {
            abort(404, 'Prize not found');
        }

        // Настройки Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);

        // Генерируем HTML из шаблона настроек
        $html = $this->buildPdfHtml($spin);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');

        try {
            $dompdf->render();
        } catch (\Exception $e) {
            $errorDetails = [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'spin_id' => $spinId,
            ];

            Log::error('PDF generation error', $errorDetails);

            // В режиме отладки показываем детальную информацию
            if (config('app.debug')) {
                $errorMessage = 'Ошибка при генерации PDF: ' . $e->getMessage() .
                               PHP_EOL . 'File: ' . basename($e->getFile()) .
                               PHP_EOL . 'Line: ' . $e->getLine();

                // Если это запрос через API, возвращаем JSON
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'error' => 'PDF generation failed',
                        'message' => $errorMessage,
                        'details' => $errorDetails,
                    ], 500);
                }

                abort(500, $errorMessage);
            } else {
                // В продакшене показываем общее сообщение
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'error' => 'PDF generation failed',
                        'message' => 'Ошибка при генерации PDF. Обратитесь к администратору.',
                    ], 500);
                }

                abort(500, 'Ошибка при генерации PDF. Обратитесь к администратору.');
            }
        }

        $filename = 'win-certificate-' . $spinId . '.pdf';

        return $dompdf->stream($filename, ['Attachment' => true]);
    }

    /**
     * Просмотреть HTML версию PDF сертификата выигрыша
     */
    public function viewWinPdfHtml(Request $request, int $spinId)
    {
        $spin = Spin::with(['prize', 'guest', 'wheel'])->find($spinId);

        if (!$spin) {
            abort(404, 'Spin not found');
        }

        if (!$spin->isWin()) {
            abort(400, 'This spin is not a win');
        }

        if (!$spin->prize) {
            abort(404, 'Prize not found');
        }

        // Генерируем HTML из шаблона настроек
        $html = $this->buildPdfHtml($spin);

        return response($html)->header('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * Построить HTML PDF из шаблона настроек
     */
    protected function buildPdfHtml(Spin $spin): string
    {
        $settings = $this->resolveEmailSettings($spin);
        $template = $settings->pdf_template;

        // Если шаблона нет, используем шаблон по умолчанию
        if (empty($template)) {
            $template = \App\Support\DefaultTemplates::pdf();
        }

        // Подготовка данных для замены
        $replacements = $this->preparePdfReplacements($spin, $settings);

        // Замена переменных в шаблоне
        $html = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template
        );

        // Логирование HTML после подстановки переменных (для отладки PDF)
//        Log::debug('PDF template after replacements', [
//            'spin_id' => $spin->id,
//            'html_preview' => mb_substr($html, 0, 5000),
//        ]);

        // Логируем финальный HTML перед нормализацией таблиц для отладки PDF
//        Log::debug('PDF HTML before table normalization', [
//            'spin_id' => $spin->id,
//            'html' => $html,
//        ]);

        // Добавляем CSS стили для таблиц и нормализуем структуру для Dompdf
        if (stripos($html, '<table') !== false || stripos($html, '<td') !== false || stripos($html, '<th') !== false || stripos($html, '<tr') !== false) {
            $html = $this->normalizeTablesHtml($html);

            // Логируем HTML после нормализации таблиц
//            Log::debug('PDF HTML after table normalization', [
//                'spin_id' => $spin->id,
//                'html_preview' => mb_substr($html, 0, 5000),
//            ]);

            $tableStyles = '';

            // Вставляем стили перед закрывающим тегом </head>
            if (stripos($html, '</head>') !== false) {
                $html = str_replace('</head>', $tableStyles . '</head>', $html);
            } elseif (stripos($html, '<style>') !== false) {
                // Если есть style, добавляем перед закрывающим тегом </style>
                $html = preg_replace('/(<\/style>)/i', $tableStyles . '$1', $html, 1);
            } else {
                // Если нет тега head, добавляем в начало body
                if (stripos($html, '<body') !== false) {
                    $html = preg_replace('/(<body[^>]*>)/i', '$1' . $tableStyles, $html, 1);
                } else {
                    // Если нет body, добавляем в начало HTML
                    $html = $tableStyles . $html;
                }
            }

        }

        return $html;
    }

    /**
     * Нормализовать структуру таблиц для Dompdf
     */
    private function normalizeTablesHtml(string $html): string
    {
        // Чистим опасные display:table-* которые могут ломать карту ячеек
        $html = preg_replace('/display\s*:\s*table-row/i', 'display:block', $html);
        $html = preg_replace('/display\s*:\s*table-cell/i', 'display:block', $html);

        $previous = libxml_use_internal_errors(true);

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new \DOMXPath($dom);

        /** @var \DOMElement $table */
        foreach ($xpath->query('//table') as $table) {
            // Если нет группировки, оборачиваем содержимое в tbody
            $hasGroup = $xpath->query('./tbody|./thead|./tfoot', $table)->length > 0;
            if (!$hasGroup) {
                $tbody = $dom->createElement('tbody');
                while ($table->firstChild) {
                    $tbody->appendChild($table->firstChild);
                }
                $table->appendChild($tbody);
            }

            // Обрабатываем группы
            foreach ($xpath->query('./tbody|./thead|./tfoot', $table) as $group) {
                // Если в группе нет строк, но есть контент, переносим в одну строку
                if ($xpath->query('./tr', $group)->length === 0) {
                    $tr = $dom->createElement('tr');
                    while ($group->firstChild) {
                        $tr->appendChild($group->firstChild);
                    }
                    $group->appendChild($tr);
                }

                // Переносим td/th, лежащие напрямую в группе, в новую строку
                foreach ($xpath->query('./td|./th', $group) as $cell) {
                    $tr = $dom->createElement('tr');
                    $tr->appendChild($cell->parentNode->removeChild($cell));
                    $group->appendChild($tr);
                }

                $rows = [];
                $maxCells = 0;

                foreach ($xpath->query('./tr', $group) as $tr) {
                    $cells = [];
                    foreach ($xpath->query('./td|./th', $tr) as $cell) {
                        $cells[] = $cell;
                    }
                    $cellCount = count($cells);
                    $maxCells = max($maxCells, $cellCount);
                    $rows[] = ['tr' => $tr, 'cells' => $cells];
                }

                // Если не было ни одной ячейки, добавляем одну пустую
                if ($maxCells === 0 && !empty($rows)) {
                    $maxCells = 1;
                }

                foreach ($rows as $row) {
                    /** @var \DOMElement $tr */
                    $tr = $row['tr'];
                    $cellCount = count($row['cells']);

                    if ($cellCount === 0) {
                        $tr->appendChild($dom->createElement('td', "\u{00A0}"));
                        $cellCount = 1;
                    }

                    while ($cellCount < $maxCells) {
                        $tr->appendChild($dom->createElement('td', "\u{00A0}"));
                        $cellCount++;
                    }
                }
            }
        }

        $normalized = $dom->saveHTML() ?: $html;
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (preg_match('~<body[^>]*>(.*)</body>~is', $normalized, $matches)) {
            return $matches[1];
        }

        return $normalized;
    }

    /**
     * Подготовить массив замен для переменных PDF
     */
    protected function preparePdfReplacements(Spin $spin, $settings): array
    {
        $prize = $spin->prize;
        $guest = $spin->guest;
        $wheel = $spin->wheel;

        // Логотип
        $logoHtml = '';
        $logoUrl = '';
        if ($settings->logo) {
            $logoUrl = $this->getFileUrl($settings->logo);
            $logoAlt = $settings->company_name ?: 'Логотип';
            $logoHtml = "<img src=\"{$logoUrl}\" alt=\"{$logoAlt}\" class=\"email-logo\">";
        }

        // Имя гостя
        $guestNameHtml = '';
        $guestName = '';
        if ($guest && $guest->name) {
            $guestNameHtml = "<div class=\"guest-name\">Уважаемый(ая) {$guest->name}!</div>";
            $guestName = $guest->name;
        }

        // Изображение приза
        $prizeImageHtml = '';
        $prizeImageUrl = '';
        if ($prize && $prize->email_image) {
            $prizeImageUrl = $this->getFileUrl($prize->email_image);
            $prizeImageAlt = $prize->getNameWithoutSeparator() ?? '';
            $prizeImageHtml = "<img src=\"{$prizeImageUrl}\" alt=\"{$prizeImageAlt}\" class=\"prize-image\">";
        }

        // Описание приза
        $prizeDescriptionHtml = '';
        if ($prize && $prize->description) {
            $prizeDescriptionHtml = "<div class=\"prize-description\">{$prize->description}</div>";
        }

        // Текст для победителя
        $prizeTextForWinnerHtml = '';
        if ($prize && $prize->text_for_winner) {
            $prizeTextForWinnerHtml = "<div class=\"prize-description\">{$prize->text_for_winner}</div>";
        }

        // Значение приза (основное отображение)
        $codeHtml = '';
        if ($prize && $prize->value) {
            $codeHtml = "<div style=\"margin: 30px 0;\">
                <div class=\"prize-code-label\">Промокод 1</div>
                <div class=\"prize-code\">{$prize->value}</div>
            </div>";
        }

        // Примечание с кодом выигрыша
        $codeNoteHtml = '';
        if ($spin->code) {
            $codeNoteHtml = "<div class=\"code-note\">Примечание: Код выигрыша {$spin->code}</div>";
        }

        // Дата
        $date = $spin->created_at->format('d.m.Y H:i');

        // Полное наименование приза
        $prizeFullName = ($prize && $prize->full_name) ? $prize->full_name : (($prize && $prize->name) ? $prize->getNameWithoutSeparator() : '');

        // Подготовка базовых замен для полей email приза
        $prizeEmailReplacements = [
            '{prize_name}' => ($prize && $prize->name) ? $prize->getNameWithoutSeparator() : '',
            '{prize_full_name}' => $prizeFullName,
            '{prize_description}' => ($prize && $prize->description) ? $prize->description : '',
            '{prize_type}' => ($prize && $prize->type) ? $prize->type : '',
            '{prize_value}' => ($prize && $prize->value) ? $prize->value : '',
            '{prize_text_for_winner}' => ($prize && $prize->text_for_winner) ? $prize->text_for_winner : '',
            '{guest_name}' => $guestName,
            '{guest_email}' => ($guest && $guest->email) ? $guest->email : '',
            '{guest_phone}' => ($guest && $guest->phone) ? $guest->phone : '',
            '{code}' => $spin->code ?: 'не указан',
            '{company_name}' => $settings->company_name ?: 'Колесо фортуны',
        ];

        // Название приза для email
        $prizeEmailName = ($prize && $prize->email_name) ? $prize->email_name : (($prize && $prize->full_name) ? $prize->full_name : (($prize && $prize->name) ? $prize->getNameWithoutSeparator() : ''));

        // Замена переменных в email_name
        if ($prize && $prize->email_name) {
            $prizeEmailName = $prize->replaceEmailVariables($prize->email_name, $prizeEmailReplacements);
        }

        $prizeEmailNameHtml = '';
        if ($prizeEmailName) {
            $prizeEmailNameHtml = "<div class=\"prize-email-name\">{$prizeEmailName}</div>";
        }

        // Текст после поздравления
        $prizeEmailTextAfterCongratulation = '';
        $prizeEmailTextAfterCongratulationHtml = '';
        if ($prize && $prize->email_text_after_congratulation) {
            $prizeEmailTextAfterCongratulation = $prize->replaceEmailVariables(
                $prize->email_text_after_congratulation,
                $prizeEmailReplacements
            );
            $prizeEmailTextAfterCongratulationHtml = "<div class=\"prize-email-text-after-congratulation\">{$prizeEmailTextAfterCongratulation}</div>";
        }

        // Текст после кода купона
        $prizeEmailCouponAfterCodeText = '';
        $prizeEmailCouponAfterCodeTextHtml = '';
        if ($prize && $prize->email_coupon_after_code_text) {
            $prizeEmailCouponAfterCodeText = $prize->replaceEmailVariables(
                $prize->email_coupon_after_code_text,
                $prizeEmailReplacements
            );
            $prizeEmailCouponAfterCodeTextHtml = "<div class=\"prize-email-coupon-after-code-text\">{$prizeEmailCouponAfterCodeText}</div>";
        }

        return [
            '{logo_html}' => $logoHtml,
            '{logo_url}' => $logoUrl,
            '{company_name}' => $settings->company_name ?: 'Колесо фортуны',
            '{wheel_name}' => ($wheel && $wheel->name) ? $wheel->name : 'Колесо Фортуны',
            '{guest_name_html}' => $guestNameHtml,
            '{guest_name}' => $guestName,
            '{guest_email}' => ($guest && $guest->email) ? $guest->email : '',
            '{guest_phone}' => ($guest && $guest->phone) ? $guest->phone : '',
            '{prize_name}' => ($prize && $prize->name) ? $prize->getNameWithoutSeparator() : '',
            '{prize_full_name}' => $prizeFullName,
            '{prize_email_name}' => $prizeEmailName,
            '{prize_email_name_html}' => $prizeEmailNameHtml,
            '{prize_description_html}' => $prizeDescriptionHtml,
            '{prize_description}' => ($prize && $prize->description) ? $prize->description : '',
            '{prize_text_for_winner_html}' => $prizeTextForWinnerHtml,
            '{prize_text_for_winner}' => ($prize && $prize->text_for_winner) ? $prize->text_for_winner : '',
            '{prize_email_text_after_congratulation}' => $prizeEmailTextAfterCongratulation,
            '{prize_email_text_after_congratulation_html}' => $prizeEmailTextAfterCongratulationHtml,
            '{prize_email_coupon_after_code_text}' => $prizeEmailCouponAfterCodeText,
            '{prize_email_coupon_after_code_text_html}' => $prizeEmailCouponAfterCodeTextHtml,
            '{prize_type}' => ($prize && $prize->type) ? $prize->type : '',
            '{prize_value}' => ($prize && $prize->value) ? $prize->value : '',
            '{prize_email_image_html}' => $prizeImageHtml,
            '{prize_email_image_url}' => $prizeImageUrl,
            '{code_html}' => $codeHtml,
            '{code_note_html}' => $codeNoteHtml,
            '{code}' =>  $prize->value ?: 'не указан', //  $spin->code ?: 'не указан',
            '{date}' => $date,
        ];
    }

    /**
     * Определить источник настроек email/PDF для спина
     */
    protected function resolveEmailSettings(Spin $spin)
    {
        $wheel = $spin->wheel;

        if ($wheel && $wheel->use_wheel_email_settings) {
            return $wheel;
        }

        return Setting::getInstance();
    }

    /**
     * Получить URL файла из storage
     */
    protected function getFileUrl(string $path): string
    {
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

    /**
     * Отправить уведомление об отключении колеса
     */
    protected function sendWheelDisabledNotification(Wheel $wheel): void
    {
        try {
            $settings = Setting::getInstance();
            
            if (!$settings->notification_email) {
                Log::info('Notification email not configured, skipping wheel disabled notification', [
                    'wheel_id' => $wheel->id,
                ]);
                return;
            }

            Mail::to($settings->notification_email)->send(new \App\Mail\WheelDisabledMail($wheel));
            
            Log::info('Wheel disabled notification sent successfully', [
                'wheel_id' => $wheel->id,
                'wheel_name' => $wheel->name,
                'notification_email' => $settings->notification_email,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send wheel disabled notification', [
                'error' => $e->getMessage(),
                'wheel_id' => $wheel->id,
                'notification_email' => $settings->notification_email ?? 'not set',
            ]);
        }
    }

    // Шаблон PDF по умолчанию переехал в App\Support\DefaultTemplates::pdf()
}

