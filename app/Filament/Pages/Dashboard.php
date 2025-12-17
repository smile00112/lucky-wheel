<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Статистика';
    protected static ?string $navigationLabel = 'Статистика';
    protected static bool $shouldRegisterNavigation = true;

    protected function getHeaderWidgets(): array
    {
        return [
            //  \App\Filament\Widgets\FilterWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}

