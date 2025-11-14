<?php

namespace App\Filament\Resources\WheelResource\Pages;

use App\Filament\Resources\WheelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListWheels extends ListRecords
{
    protected static string $resource = WheelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()->forUser();
    }
}



