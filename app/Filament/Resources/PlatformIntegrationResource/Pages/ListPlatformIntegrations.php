<?php

namespace App\Filament\Resources\PlatformIntegrationResource\Pages;

use App\Filament\Resources\PlatformIntegrationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlatformIntegrations extends ListRecords
{
    protected static string $resource = PlatformIntegrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

