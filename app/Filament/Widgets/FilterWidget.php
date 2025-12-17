<?php

namespace App\Filament\Widgets;

use App\Models\Wheel;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Widgets\Widget;

class FilterWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static ?int $sort = 0;
    protected int | string | array $columnSpan = 'full';

    public ?string $dateFilter = '30days';
    public ?int $wheelFilter = null;

    public function mount(): void
    {
//        $this->form->fill([
//            'dateFilter' => $this->dateFilter,
//            'wheelFilter' => $this->wheelFilter,
//        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('dateFilter')
                    ->label('Период')
                    ->options([
                        'today' => 'Сегодня',
                        'yesterday' => 'Сегодня',
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
                        $this->dispatch('updateWidgets', filter: $state, wheelId: $this->wheelFilter);
                    }),
                Select::make('wheelFilter')
                    ->label('Колесо')
                    ->options(function () {
                        return Wheel::forUser()
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->prepend('Все колёса', null)
                            ->toArray();
                    })
                    ->placeholder('Выберите колесо')
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->wheelFilter = $state;
                        $this->dispatch('updateWidgets', filter: $this->dateFilter, wheelId: $state);
                    }),
            ])
            ->columns(2);
    }

    protected string $view = 'filament.widgets.filter-widget';
}

