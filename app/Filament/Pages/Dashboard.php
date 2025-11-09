<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;

class Dashboard extends BaseDashboard implements HasForms
{
    use InteractsWithForms;

    public ?string $dateFilter = '30days';

    public function mount(): void
    {
        $this->form->fill([
            'dateFilter' => '30days',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
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
            \App\Filament\Widgets\DateFilterWidget::class,
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

