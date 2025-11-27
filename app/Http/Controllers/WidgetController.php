<?php

namespace App\Http\Controllers;

use App\Events\PrizeWon;
use App\Models\Wheel;
use App\Models\Guest;
use App\Models\GuestIpAddress;
use App\Models\Spin;
use App\Models\Prize;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        return view('widget.wheel-v3', compact('wheel', 'guest'));
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
                'error' => 'Wheel not available yet',
            ], 404);
        }
        if ($wheel->ends_at && $wheel->ends_at->isPast()) {
            return response()->json([
                'error' => 'Wheel has expired',
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
                'description' => $prize->description,
                'color' => $prize->color,
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
            'win_notification_title' => '🎉 Поздравляем с выигрышем!',
            'win_notification_win_text' => 'Вы выиграли:',
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
        ];

        $settings = $wheel->settings ?? [];
        $texts = array_merge($defaultTexts, $settings);

        return response()->json([
            'id' => $wheel->id,
            'name' => $wheel->name,
            'description' => $wheel->description,
            'slug' => $wheel->slug,
            'spins_limit' => $wheel->spins_limit,
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
        if ($clientIp) {
            // Ищем последний выигрыш с этого IP адреса для этого колеса
            $lastWinByIp = Spin::where('wheel_id', $wheel->id)
                ->whereNotNull('prize_id')
                ->where(function ($query) use ($clientIp) {
                    // Проверяем IP в основной таблице spins
                    $query->where('ip_address', $clientIp)
                        // Или проверяем через связанные IP адреса гостей
                        ->orWhereHas('guest', function ($q) use ($clientIp) {
                            $q->where('ip_address', $clientIp)
                                ->orWhereHas('ipAddresses', function ($ipq) use ($clientIp) {
                                    $ipq->where('ip_address', $clientIp);
                                });
                        });
                })
                ->orderBy('created_at', 'desc')
                ->first();
        }

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
                        ],
                        'code' => $lastWin->code, // Код из spin
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

            $guestHasData = !empty($guest->email) && !empty($guest->phone) && !empty($guest->name);

            return response()->json([
                'spin_id' => $spin->id,
                'prize' => $prize ? [
                    'id' => $prize->id,
                    'name' => $prize->name,
                    'description' => $prize->description,
                    'text_for_winner' => $prize->text_for_winner,
                    'type' => $prize->type,
                    'email_image' => $prize->email_image,
                ] : null,
                'code' => $spin->code, // Код из spin, а не value из prize
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
        if ($clientIp) {
            // Ищем последний выигрыш с этого IP адреса для этого колеса
            $lastWinByIp = Spin::where('wheel_id', $wheel->id)
                ->whereNotNull('prize_id')
                ->where(function ($query) use ($clientIp) {
                    // Проверяем IP в основной таблице spins
                    $query->where('ip_address', $clientIp)
                        // Или проверяем через связанные IP адреса гостей
                        ->orWhereHas('guest', function ($q) use ($clientIp) {
                            $q->where('ip_address', $clientIp)
                                ->orWhereHas('ipAddresses', function ($ipq) use ($clientIp) {
                                    $ipq->where('ip_address', $clientIp);
                                });
                        });
                })
                ->orderBy('created_at', 'desc')
                ->with(['prize', 'guest'])
                ->first();
        }

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
                    $hasData = !empty($winGuest->email) && !empty($winGuest->phone) && !empty($winGuest->name);
                }

                return response()->json([
                    'has_win' => true,
                    'spin_id' => $lastWin->id, // ID спина для отправки приза
                    'prize' => [
                        'id' => $lastWin->prize->id,
                        'name' => $lastWin->prize->name,
                        'text_for_winner' => $lastWin->prize->text_for_winner,
                        'type' => $lastWin->prize->type,
                        'email_image' => $lastWin->prize->email_image,
                    ],
                    'code' => $lastWin->code, // Код из spin
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
                    'code' => $spin->code, // Код из spin
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
        $hasData = !empty($guest->email) || !empty($guest->phone) || !empty($guest->name);

        return response()->json([
            'id' => $guest->id,
            'has_data' => $hasData,
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
        $dompdf->render();

        $filename = 'win-certificate-' . $spinId . '.pdf';

        return $dompdf->stream($filename, ['Attachment' => true]);
    }

    /**
     * Построить HTML PDF из шаблона настроек
     */
    protected function buildPdfHtml(Spin $spin): string
    {
        $settings = Setting::getInstance();
        $template = $settings->pdf_template;

        // Если шаблона нет, используем шаблон по умолчанию
        if (empty($template)) {
            $template = $this->getDefaultPdfTemplate();
        }

        // Подготовка данных для замены
        $replacements = $this->preparePdfReplacements($spin, $settings);

        // Замена переменных в шаблоне
        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template
        );
    }

    /**
     * Подготовить массив замен для переменных PDF
     */
    protected function preparePdfReplacements(Spin $spin, Setting $settings): array
    {
        $prize = $spin->prize;
        $guest = $spin->guest;
        $wheel = $spin->wheel;

        // Имя гостя
        $guestNameHtml = '';
        $guestName = '';
        if ($guest && $guest->name) {
            $guestNameHtml = "<div class=\"guest-name\">Уважаемый {$guest->name}!</div>";
            $guestName = $guest->name;
        }

        // Изображение приза
        $prizeImageHtml = '';
        $prizeImageUrl = '';
        if ($prize && $prize->email_image) {
            $prizeImageUrl = $this->getFileUrl($prize->email_image);
            $prizeImageAlt = $prize->name ?? '';
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
                <div class=\"prize-code-label\">Идентификационный номер</div>
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

        return [
            '{company_name}' => $settings->company_name ?: 'Колесо фортуны',
            '{wheel_name}' => ($wheel && $wheel->name) ? $wheel->name : 'Колесо Фортуны',
            '{guest_name_html}' => $guestNameHtml,
            '{guest_name}' => $guestName,
            '{guest_email}' => ($guest && $guest->email) ? $guest->email : '',
            '{guest_phone}' => ($guest && $guest->phone) ? $guest->phone : '',
            '{prize_name}' => ($prize && $prize->name) ? $prize->name : '',
            '{prize_description_html}' => $prizeDescriptionHtml,
            '{prize_description}' => ($prize && $prize->description) ? $prize->description : '',
            '{prize_text_for_winner_html}' => $prizeTextForWinnerHtml,
            '{prize_text_for_winner}' => ($prize && $prize->text_for_winner) ? $prize->text_for_winner : '',
            '{prize_type}' => ($prize && $prize->type) ? $prize->type : '',
            '{prize_value}' => ($prize && $prize->value) ? $prize->value : '',
            '{prize_email_image_html}' => $prizeImageHtml,
            '{prize_email_image_url}' => $prizeImageUrl,
            '{code_html}' => $codeHtml,
            '{code_note_html}' => $codeNoteHtml,
            '{code}' => $spin->code ?: 'не указан',
            '{date}' => $date,
        ];
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
     * Получить шаблон PDF по умолчанию
     */
    protected function getDefaultPdfTemplate(): string
    {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Сертификат выигрыша</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            background: #667eea;
            padding: 40px;
            color: #333;
        }
        .certificate {
            background: white;
            max-width: 800px;
            margin: 0 auto;
            padding: 60px 40px;
            border-radius: 20px;
            text-align: center;
        }
        .certificate-header {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 20px;
        }
        .certificate-title {
            font-size: 28px;
            color: #333;
            margin-bottom: 40px;
            font-weight: bold;
        }
        .prize-name {
            font-size: 32px;
            color: #764ba2;
            font-weight: bold;
            margin: 30px 0;
            padding: 20px;
            background: #f5f7fa;
            border-radius: 10px;
        }
        .guest-name {
            font-size: 22px;
            color: #667eea;
            margin: 20px 0;
            font-weight: 600;
        }
        .prize-code {
            font-size: 28px;
            color: #667eea;
            margin: 30px 0;
            padding: 20px 30px;
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border: 3px solid #667eea;
            border-radius: 12px;
            font-family: \'DejaVu Sans\', Arial, sans-serif;
            font-weight: bold;
            letter-spacing: 4px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        .prize-code-label {
            font-size: 14px;
            color: #667eea;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .prize-description {
            font-size: 16px;
            color: #666;
            margin: 20px 0;
            line-height: 1.6;
        }
        .certificate-footer {
            margin-top: 50px;
            padding-top: 30px;
            border-top: 2px solid #e0e0e0;
            font-size: 14px;
            color: #999;
        }
        .date {
            margin-top: 20px;
            font-size: 14px;
            color: #999;
        }
        .wheel-name {
            font-size: 18px;
            color: #667eea;
            margin-bottom: 10px;
        }
        .prize-image {
            max-width: 100%;
            max-height: 300px;
            margin: 20px auto;
            display: block;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .code-note {
            font-size: 12px;
            color: #999;
            margin-top: 20px;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="certificate">
        <div class="certificate-header">ПОЗДРАВЛЯЕМ!</div>
        <div class="certificate-title">Сертификат выигрыша</div>

        {guest_name_html}

        <div class="wheel-name">{wheel_name}</div>

        <div class="prize-name">Название приза: {prize_name}</div>

        {prize_email_image_html}

        {prize_description_html}

        {code_html}

        {prize_text_for_winner_html}

        {code_note_html}

        <div class="certificate-footer">
            <div class="date">Дата выигрыша: {date}</div>
        </div>
    </div>
</body>
</html>';
    }
}

