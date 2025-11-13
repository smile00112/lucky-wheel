<?php

namespace App\Filament\Widgets;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Schema;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class DateFilterWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.widgets.date-filter-widget';

    public ?string $dateFilter = 'all';
    public ?string $startDate = null;
    public ?string $endDate = null;

    protected int | string | array $columnSpan = 'full';

    public function mount(): void
    {
        $this->dateFilter = 'all';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('dateFilter')
                    ->label('Период')
                    ->options([
                        'today' => 'Сегодня',
                        'yesterday' => 'Вчера',
                        '7days' => 'Последние 7 дней',
                        '30days' => 'Последние 30 дней',
                        '90days' => 'Последние 90 дней',
                        'year' => 'Год',
                        'all' => 'Всё время',
                        'custom' => 'Свой диапазон',
                    ])
                    ->default('30days')
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->dateFilter = $state;
                    })
                    ->columnSpanFull(),

                DatePicker::make('startDate')
                    ->label('Дата начала')
                    ->displayFormat('d.m.Y')
                    ->native(false)
                    ->visible(fn ($get) => $get('dateFilter') === 'custom')
                    ->required(fn ($get) => $get('dateFilter') === 'custom')
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->startDate = $state;
                    })
                    ->columnSpan(1),
                DatePicker::make('endDate')
                    ->label('Дата окончания')
                    ->displayFormat('d.m.Y')
                    ->native(false)
                    ->visible(fn ($get) => $get('dateFilter') === 'custom')
                    ->required(fn ($get) => $get('dateFilter') === 'custom')
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->endDate = $state;
                        // Автоматически устанавливаем дату начала, если она не задана
                        if ($state && !$this->startDate) {
                            $this->startDate = Carbon::parse($state)->subDays(30)->format('Y-m-d');
                        }
                    })
                    ->columnSpan(1),

                    Actions::make([
                        Action::make('apply')
                            ->label('Применить')
                            ->action('applyFilter')
                            ->color('primary'),
                    ])
            ])
            ->columns(2)
            //->statePath('dateFilter')
        ;
    }

    public function applyFilter(): void
    {
        $formData = $this->form->getState();
        $dateFilter = $formData['dateFilter'] ?? $this->dateFilter;
        $startDate = $formData['startDate'] ?? $this->startDate;
        $endDate = $formData['endDate'] ?? $this->endDate;

        if ($dateFilter === 'custom') {
            if ($startDate && $endDate) {
                $this->dispatch('updateWidgets',
                    filter: 'custom',
                    startDate: $startDate,
                    endDate: $endDate
                );
            }
        } else {
            $this->dispatch('updateWidgets', filter: $dateFilter);
        }
    }
}
