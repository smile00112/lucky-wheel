<?php

namespace App\Filament\Widgets;

use App\Models\Spin;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class WinsByGuestChart extends ChartWidget
{
    protected ?string $heading = 'Топ гостей по купонам';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    public ?string $filter = 'all';
    public ?string $customStartDate = null;
    public ?string $customEndDate = null;
    public ?int $wheelId = null;

    //protected $listeners = ['updateWidgets'];
    protected function getListeners(): array
    {
        return [
            'updateWidgets',
        ];
    }

    public function updateWidgets($filter = null, $startDate = null, $endDate = null, $wheelId = null): void
    {
        $this->filter = $filter ?? '30days';
        $this->customStartDate = $startDate;
        $this->customEndDate = $endDate;
        $this->wheelId = $wheelId;
    }

    protected function getFilters(): ?array
    {
        // Убираем фильтры из виджета, так как они будут на странице
        return null;
    }

    protected function getData(): array
    {
        // Получаем фильтр из свойства виджета
        $dateFilter = $this->filter ?? '30days';
        $startDate = $this->getStartDate($dateFilter);
        $endDate = $this->getEndDate($dateFilter);

        $query = Spin::whereNotNull('spins.prize_id')
            ->whereBetween('spins.created_at', [$startDate, $endDate]);

        if ($this->wheelId) {
            $query->where('wheel_id', $this->wheelId);
        }

        $user = auth()->user();
        if ($user && $user->isManager()) {
            $query->whereHas('wheel', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        $wins = $query->with('guest')
            ->get()
            ->groupBy('guest_id')
            ->map(function ($spins) {
                $guest = $spins->first()->guest;
                $guestName = $guest->name ?: $guest->email ?: $guest->phone ?: ('Гость #' . $guest->id);
                return [
                    'guest_name' => $guestName,
                    'count' => $spins->count(),
                ];
            })
            ->sortByDesc('count')
            ->take(10)
            ->values();

        return [
            'datasets' => [
                [
                    'label' => 'Количество выигрышей',
                    'data' => $wins->pluck('count')->toArray(),
                    'backgroundColor' => 'rgba(54, 162, 235, 0.8)',
                ],
            ],
            'labels' => $wins->pluck('guest_name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getStartDate(?string $filter = null): Carbon
    {
        $filter = $filter ?? $this->filter ?? '30days';

        if ($filter === 'custom' && $this->customStartDate) {
            return Carbon::parse($this->customStartDate)->startOfDay();
        }

        return match ($filter) {
            'today' => now()->startOfDay(),
            'yesterday' => now()->subDay()->startOfDay(),
            '7days' => now()->subDays(7),
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            'year' => now()->subYear(),
            'all' => Carbon::create(2020, 1, 1),
            default => now()->subDays(30),
        };
    }

    protected function getEndDate(?string $filter = null): Carbon
    {
        $filter = $filter ?? $this->filter ?? '30days';

        if ($filter === 'custom' && $this->customEndDate) {
            return Carbon::parse($this->customEndDate)->endOfDay();
        }

        return match ($filter) {
            'today' => now()->endOfDay(),
            'yesterday' => now()->subDay()->endOfDay(),
            default => now()->endOfDay(),
        };
    }
}
