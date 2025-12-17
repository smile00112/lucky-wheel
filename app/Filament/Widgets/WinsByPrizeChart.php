<?php

namespace App\Filament\Widgets;

use App\Models\Spin;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class WinsByPrizeChart extends ChartWidget
{
    protected ?string $heading = 'Статистика выигранных купонов';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    public ?string $filter = 'all';
    public ?string $customStartDate = null;
    public ?string $customEndDate = null;
    public ?int $wheelId = null;

    protected function getListeners(): array
    {
        return [
            'updateWidgets',
        ];
    }

    public function updateWidgets($filter = null, $startDate = null, $endDate = null, $wheelId = null): void
    {
        $this->filter = $filter ?? 'all';
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
        $dateFilter = $this->filter ?? 'all';
        $startDate = $this->getStartDate($dateFilter);
        $endDate = $this->getEndDate($dateFilter);

        $query = Spin::whereNotNull('spins.prize_id')
            ->whereBetween('spins.created_at', [$startDate, $endDate])
            ->join('prizes', 'spins.prize_id', '=', 'prizes.id')
            ->join('wheels', 'prizes.wheel_id', '=', 'wheels.id');

        if ($this->wheelId) {
            $query->where('spins.wheel_id', $this->wheelId);
        }

        $user = auth()->user();
        if ($user && $user->isManager()) {
            $query->where('wheels.user_id', $user->id);
        }

        $wins = $query->select('prizes.name', DB::raw('COUNT(*) as count'))
            ->groupBy('prizes.id', 'prizes.name')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Количество выигрышей',
                    'data' => $wins->pluck('count')->toArray(),
                    'backgroundColor' => [
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(153, 102, 255, 0.8)',
                        'rgba(255, 159, 64, 0.8)',
                        'rgba(199, 199, 199, 0.8)',
                        'rgba(83, 102, 255, 0.8)',
                        'rgba(255, 99, 255, 0.8)',
                        'rgba(99, 255, 132, 0.8)',
                    ],
                ],
            ],
            'labels' => $wins->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getStartDate(?string $filter = null): Carbon
    {
        $filter = $filter ?? $this->filter ?? 'all';

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
