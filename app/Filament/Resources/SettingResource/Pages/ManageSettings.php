<?php

namespace App\Filament\Resources\SettingResource\Pages;

use App\Filament\Resources\SettingResource;
use App\Models\Setting;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class ManageSettings extends EditRecord
{
    protected static string $resource = SettingResource::class;

    protected static ?string $title = 'Настройки';

    protected static ?string $navigationLabel = 'Настройки';

    public function mount(int | string $record = null): void
    {
        // Получаем единственную запись настроек или создаем новую
        $setting = Setting::getInstance();
        
        // Если запись не существует, создаем её
        if (!$setting->exists) {
            $setting->save();
        }
        
        parent::mount($setting->id);
    }

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

