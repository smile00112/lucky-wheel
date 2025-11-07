<?php

namespace App\Filament\Resources\SpinResource\Pages;

use App\Filament\Resources\SpinResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSpin extends EditRecord
{
    protected static string $resource = SpinResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}




