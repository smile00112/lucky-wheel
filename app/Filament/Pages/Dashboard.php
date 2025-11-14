<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;

class Dashboard extends BaseDashboard implements HasForms
{
    use InteractsWithForms;

    protected static ?string $title = 'Статистика';
    protected static ?string $navigationLabel = 'Статистика';
    protected static bool $shouldRegisterNavigation = true; // или false, чтобы скрыть

    public ?string $dateFilter = '30days';

    public function mount(): void
    {

    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('dateFilter')
                    ->label('Период')
                    ->options([
                        '7days' => 'Последние 7 дней',
                        '30days' => 'Последние 30 дней',
                        '90days' => 'Последние 90 дней',
                        'year' => 'Год',
                        'all' => 'Всё время',
                    ])
                    ->default('30days')
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->dateFilter = $state;
                        $this->dispatch('updateWidgets', filter: $state);
                    }),
            ])
            ->statePath('dateFilter');
    }

    protected function getHeaderWidgets(): array
    {
        return [
           // \App\Filament\Widgets\DateFilterWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getDateFilterLabel(): string
    {
        return match ($this->dateFilter) {
            '7days' => 'Последние 7 дней',
            '30days' => 'Последние 30 дней',
            '90days' => 'Последние 90 дней',
            'year' => 'Год',
            'all' => 'Всё время',
            default => 'Последние 30 дней',
        };
    }
}

