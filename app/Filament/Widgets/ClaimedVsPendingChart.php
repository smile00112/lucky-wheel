<?php

namespace App\Filament\Widgets;

use App\Models\Spin;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class ClaimedVsPendingChart extends ChartWidget
{
    protected ?string $heading = 'Полученные vs Неполученные выигрыши';

    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    public ?string $filter = '30days';
    public ?string $customStartDate = null;
    public ?string $customEndDate = null;

    protected $listeners = ['updateWidgets' => 'updateFilter'];

    public function updateFilter($filter = null, $startDate = null, $endDate = null): void
    {
        if (is_array($filter)) {
            $this->filter = $filter['filter'] ?? $filter;
            $this->customStartDate = $filter['startDate'] ?? null;
            $this->customEndDate = $filter['endDate'] ?? null;
        } else {
            $this->filter = $filter;
            $this->customStartDate = $startDate;
            $this->customEndDate = $endDate;
        }
    }

    protected function getFilters(): ?array
    {
        return null;
    }

    protected function getData(): array
    {
        $dateFilter = $this->filter ?? '30days';
        $startDate = $this->getStartDate($dateFilter);
        $endDate = $this->getEndDate($dateFilter);

        $claimedWins = Spin::whereNotNull('spins.prize_id')
            ->where('spins.status', 'claimed')
            ->whereBetween('spins.created_at', [$startDate, $endDate])
            ->count();

        $pendingWins = Spin::whereNotNull('spins.prize_id')
            ->where('spins.status', 'pending')
            ->whereBetween('spins.created_at', [$startDate, $endDate])
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'Выигрыши',
                    'data' => [$claimedWins, $pendingWins],
                    'backgroundColor' => [
                        'rgba(34, 197, 94, 0.8)', // Зелёный для полученных
                        'rgba(239, 68, 68, 0.8)', // Красный для неполученных
                    ],
                ],
            ],
            'labels' => ['Полученные', 'Неполученные'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
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
