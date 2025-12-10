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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Проверяем уникальность комбинации platform + wheel_id
        if (isset($data['platform']) && isset($data['wheel_id']) && $data['wheel_id'] !== null) {
            $exists = \App\Models\PlatformIntegration::where('platform', $data['platform'])
                ->where('wheel_id', $data['wheel_id'])
                ->where('id', '!=', $this->record->id)
                ->exists();
            
            if ($exists) {
                throw new \Illuminate\Validation\ValidationException(
                    validator([], []),
                    ['wheel_id' => ['Интеграция для этой платформы и колеса уже существует.']]
                );
            }
        }
        
        return $data;
    }
}




