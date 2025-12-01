<?php

namespace App\Filament\Resources\SettingResource\Pages;

use App\Filament\Resources\SettingResource;
use App\Models\Setting;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class ManageSettings extends EditRecord
{
    protected static string $resource = SettingResource::class;

    protected static ?string $title = 'Настройки';

    protected static ?string $navigationLabel = 'Настройки';

    public function mount(int | string $record = null): void
    {
        $user = auth()->user();
        if (!$user) {
            abort(403);
        }

        $owner = $user->getOwner();
        $setting = Setting::getForUser($owner);
        
        parent::mount($setting->id);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = auth()->user();
        if ($user) {
            $owner = $user->getOwner();
            $data['user_id'] = $owner->id;
        }
        
        return $data;
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

