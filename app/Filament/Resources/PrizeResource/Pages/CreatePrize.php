<?php

namespace App\Filament\Resources\PrizeResource\Pages;

use App\Filament\Resources\PrizeResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePrize extends CreateRecord
{
    protected static string $resource = PrizeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (!isset($data['sector_view'])) {
            $data['sector_view'] = 'text_with_image';
        }

        return $data;
    }
}




