<?php

namespace App\Filament\Resources\SpinResource\Pages;

use App\Filament\Resources\SpinResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListSpins extends ListRecords
{
    protected static string $resource = SpinResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()->forCompany();
    }
}




