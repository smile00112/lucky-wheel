<?php

namespace App\Filament\Resources\WheelResource\Pages;

use App\Filament\Resources\WheelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWheel extends EditRecord
{
    protected static string $resource = WheelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}


