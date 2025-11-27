<?php

namespace App\Filament\Resources\PlatformIntegrationResource\Pages;

use App\Filament\Resources\PlatformIntegrationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlatformIntegration extends EditRecord
{
    protected static string $resource = PlatformIntegrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}



