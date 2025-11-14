<?php

namespace App\Http\Controllers;

use App\Models\Wheel;
use App\Models\Guest;
use App\Models\GuestIpAddress;
use App\Models\Spin;
use App\Models\Prize;
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
     * Отобразить виджет для iframe
     */
    public function embed(string $slug)
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

        // Проверяем, нужен ли только контент (без HTML структуры)
        $contentOnly = request()->query('content_only', false);

//        if ($contentOnly) {
//            return view('widget.wheel-content', compact('wheel'));
//        }
//
//        return view('widget.wheel', compact('wheel'));

        return view('widget.wheel', compact('wheel'));
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
                // Если изображение - это полный URL, используем как есть
                if (filter_var($prize->image, FILTER_VALIDATE_URL)) {
                    $imageUrl = $prize->image;
                } elseif (str_starts_with($prize->image, '/')) {
                    // Если путь начинается с /, это абсолютный путь
                    $imageUrl = url($prize->image);
                } elseif (Storage::disk('public')->exists($prize->image)) {
                    // Если файл в public storage
                    $imageUrl = Storage::disk('public')->url($prize->image);
                } else {
                    // По умолчанию используем asset для storage
                    $imageUrl = asset('storage/' . ltrim($prize->image, '/'));
                }
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

        return response()->json([
            'id' => $wheel->id,
            'name' => $wheel->name,
            'description' => $wheel->description,
            'slug' => $wheel->slug,
            'spins_limit' => $wheel->spins_limit,
            'prizes' => $prizes,
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
                    $hasData = !empty($winGuest->email) || !empty($winGuest->phone) || !empty($winGuest->name);
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
        $guest = Guest::find($guestId);

        if (!$guest) {
            return response()->json([
                'error' => 'Guest not found',
            ], 404);
        }

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

        // Получаем URL изображения для email
        $emailImageUrl = null;
        if ($spin->prize->email_image) {
            // Если изображение - это полный URL, используем как есть
            if (filter_var($spin->prize->email_image, FILTER_VALIDATE_URL)) {
                $emailImageUrl = $spin->prize->email_image;
            } elseif (str_starts_with($spin->prize->email_image, '/')) {
                // Если путь начинается с /, это абсолютный путь
                $emailImageUrl = url($spin->prize->email_image);
            } elseif (Storage::disk('public')->exists($spin->prize->email_image)) {
                // Если файл в public storage
                $emailImageUrl = Storage::disk('public')->url($spin->prize->email_image);
            } else {
                // По умолчанию используем asset для storage
                $emailImageUrl = asset('storage/' . ltrim($spin->prize->email_image, '/'));
            }
        }

        // Настройки Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);

        // Генерируем HTML из Blade шаблона
        $html = view('pdf.win-certificate', [
            'prize' => $spin->prize,
            'code' => $spin->code,
            'wheel' => $spin->wheel,
            'date' => $spin->created_at->format('d.m.Y H:i'),
            'emailImageUrl' => $emailImageUrl,
        ])->render();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'win-certificate-' . $spinId . '.pdf';

        return $dompdf->stream($filename, ['Attachment' => true]);
    }
}

