<?php

namespace App\Filament\Widgets;

use App\Models\Spin;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class WinsByGuestChart extends ChartWidget
{
    protected ?string $heading = 'Топ гостей по выигрышам';

    protected static ?int $sort = 4;

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
        // Убираем фильтры из виджета, так как они будут на странице
        return null;
    }

    protected function getData(): array
    {
        // Получаем фильтр из свойства виджета
        $dateFilter = $this->filter ?? '30days';
        $startDate = $this->getStartDate($dateFilter);
        $endDate = $this->getEndDate($dateFilter);

        $wins = Spin::whereNotNull('spins.prize_id')
            ->whereBetween('spins.created_at', [$startDate, $endDate])
            ->with('guest')
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
