<?php

namespace App\Filament\Resources\SettingResource\Pages;

use App\Filament\Resources\SettingResource;
use App\Models\Setting;
use Filament\Resources\Pages\ListRecords;

class ListSettings extends ListRecords
{
    protected static string $resource = SettingResource::class;

    public function mount(): void
    {
        // Редиректим на страницу редактирования единственной записи
        $setting = Setting::getInstance();
        
        if (!$setting->exists) {
            $setting->save();
        }
        
        redirect(SettingResource::getUrl('edit', ['record' => $setting->id]));
    }
}

