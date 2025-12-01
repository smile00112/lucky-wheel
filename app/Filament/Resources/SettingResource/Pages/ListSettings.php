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
        $user = auth()->user();
        if (!$user) {
            abort(403);
        }

        $owner = $user->getOwner();
        $setting = Setting::getForUser($owner);
        
        redirect(SettingResource::getUrl('edit', ['record' => $setting->id]));
    }
}

