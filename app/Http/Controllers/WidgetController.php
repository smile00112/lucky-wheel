<?php

namespace App\Http\Controllers;

use App\Models\Wheel;
use App\Models\Guest;
use App\Models\Spin;
use App\Models\Prize;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

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
            $imageUrl = null;
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

            return [
                'id' => $prize->id,
                'name' => $prize->name,
                'description' => $prize->description,
                'color' => $prize->color,
                'probability' => (float) $prize->probability,
                'type' => $prize->type,
                'value' => $prize->value,
                'image' => $imageUrl,
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

        // Проверка выигрыша сегодня - блокируем повторное вращение до полуночи
        $todayWin = Spin::where('guest_id', $guest->id)
            ->where('wheel_id', $wheel->id)
            ->whereNotNull('prize_id')
            ->whereDate('created_at', today())
            ->first();

        if ($todayWin) {
            $prize = $todayWin->prize;
            return response()->json([
                'error' => 'Already won today',
                'message' => 'Вы уже выиграли сегодня. Попробуйте завтра!',
                'today_win' => [
                    'prize' => [
                        'id' => $prize->id,
                        'name' => $prize->name,
                        'value' => $prize->value,
                        'text_for_winner' => $prize->text_for_winner,
                        'type' => $prize->type,
                    ],
                ],
            ], 403);
        }

        try {
            DB::beginTransaction();

            // Выбор приза с учетом вероятностей и лимитов
            $prize = $this->selectRandomPrize($wheel, $guest->id);
            //$prize = $this->selectPrize($wheel, $guest->id);

            // Создание записи о вращении
            $spin = Spin::create([
                'wheel_id' => $wheel->id,
                'guest_id' => $guest->id,
                'prize_id' => $prize ? $prize->id : null,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => $prize ? 'completed' : 'completed',
                'metadata' => [
                    'referer' => $request->header('Referer'),
                    'origin' => $request->header('Origin'),
                ],
            ]);

            // Увеличение счетчика использованных призов, если приз был выигран
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
                    'value' => $prize->value,
                ] : null,
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

        $todayWin = Spin::where('guest_id', $guestId)
            ->where('wheel_id', $wheel->id)
            ->whereNotNull('prize_id')
            ->whereDate('created_at', today())
            ->with('prize')
            ->first();

        if ($todayWin && $todayWin->prize) {
            return response()->json([
                'has_win' => true,
                'prize' => [
                    'id' => $todayWin->prize->id,
                    'name' => $todayWin->prize->name,
                    'value' => $todayWin->prize->value,
                    'text_for_winner' => $todayWin->prize->text_for_winner,
                    'type' => $todayWin->prize->type,
                ],
                'win_date' => $todayWin->created_at->toIso8601String(),
            ]);
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
                        'value' => $spin->prize->value,
                    ] : null,
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
    private function selectPrize(Wheel $wheel, int $guestId): ?Prize
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
}

