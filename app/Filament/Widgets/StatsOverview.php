<?php

namespace App\Filament\Widgets;

use App\Models\Spin;
use App\Models\Guest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;

class StatsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

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

    protected function getStats(): array
    {
        // Получаем фильтр из свойства виджета
        $dateFilter = $this->filter ?? '30days';
        $startDate = $this->getStartDate($dateFilter);
        $endDate = $this->getEndDate($dateFilter);

        $totalSpins = Spin::whereBetween('spins.created_at', [$startDate, $endDate])
            ->count();

        $claimedWins = Spin::whereNotNull('spins.prize_id')
            ->where('spins.status', 'claimed')
            ->whereBetween('spins.created_at', [$startDate, $endDate])
            ->count();

        $pendingWins = Spin::whereNotNull('spins.prize_id')
            ->where('spins.status', 'pending')
            ->whereBetween('spins.created_at', [$startDate, $endDate])
            ->count();

        $totalWins = $claimedWins + $pendingWins;
        $claimedRate = $totalWins > 0 ? round(($claimedWins / $totalWins) * 100, 2) : 0;

        $uniqueGuests = Spin::whereBetween('spins.created_at', [$startDate, $endDate])
            ->distinct()
            ->count('spins.guest_id');

        return [
            Stat::make('Всего вращений', $totalSpins)
                ->description('За выбранный период')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('info'),
            Stat::make('Использовано выигрышей', $claimedWins)
                ->description('За выбранный период')
                ->descriptionIcon('heroicon-m-gift')
                ->color('success'),
            Stat::make('Процент использованных выигрышей', $claimedRate . '%')
                ->description('От общего количества выигрышей')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($claimedRate > 50 ? 'success' : ($claimedRate > 25 ? 'warning' : 'danger')),
            Stat::make('Уникальных гостей', $uniqueGuests)
                ->description('За выбранный период')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
        ];
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
