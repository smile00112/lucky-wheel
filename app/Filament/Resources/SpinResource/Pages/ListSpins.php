<?php

namespace App\Filament\Resources\SpinResource\Pages;

use App\Filament\Resources\SpinResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSpins extends ListRecords
{
    protected static string $resource = SpinResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}



