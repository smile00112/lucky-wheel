<?php

namespace App\Filament\Widgets;

use App\Models\Spin;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SpinsByDayChart extends ChartWidget
{
    protected ?string $heading = 'Количество вращений по дням';

    protected static ?int $sort = 6;

    protected int | string | array $columnSpan = [
        'md' => 2,
        'xl' => 2,
    ];

    public ?string $filter = 'all';
    public ?string $customStartDate = null;
    public ?string $customEndDate = null;

    protected function getListeners(): array
    {
        return [
            'updateWidgets',
        ];
    }

    public function updateWidgets($filter = null, $startDate = null, $endDate = null): void
    {
        $this->filter = $filter ?? 'all';
        $this->customStartDate = $startDate;
        $this->customEndDate = $endDate;
    }

    protected function getFilters(): ?array
    {
        return null;
    }

    protected function getData(): array
    {
        $dateFilter = $this->filter ?? 'all';
        $startDate = $this->getStartDate($dateFilter);
        $endDate = $this->getEndDate($dateFilter);

        $query = $this->getFilteredSpinQuery()
            ->whereBetween('spins.created_at', [$startDate, $endDate]);

        $spins = $query
            ->select(
                DB::raw('DATE(spins.created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        $dates = [];
        $counts = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $dates[] = $currentDate->format('d.m.Y');

            $spin = $spins->firstWhere('date', $dateStr);
            $counts[] = $spin ? $spin->count : 0;

            $currentDate->addDay();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Количество вращений',
                    'data' => $counts,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $dates,
        ];
    }

    protected function getType(): string
    {
        return 'line';
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
            'all' => Carbon::create(2025, 10, 31),
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

    protected function getFilteredSpinQuery()
    {
        $query = Spin::query();
        $user = auth()->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isOwner()) {
            return $query;
        }

        return $query->whereHas('wheel', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });
    }
}







