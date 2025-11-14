<?php

namespace App\Filament\Resources\PrizeResource\Pages;

use App\Filament\Resources\PrizeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPrize extends EditRecord
{
    protected static string $resource = PrizeResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        $user = auth()->user();
        if ($user && $user->isManager() && $this->record->wheel->user_id !== $user->id) {
            abort(403, 'У вас нет доступа к этому призу');
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = auth()->user();
        if ($user && $user->isManager()) {
            // Менеджер не может менять wheel_id
            $data['wheel_id'] = $this->record->wheel_id;
        }

        return $data;
    }
}




