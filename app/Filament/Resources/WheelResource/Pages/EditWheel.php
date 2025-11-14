<?php

namespace App\Filament\Resources\WheelResource\Pages;

use App\Filament\Resources\WheelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWheel extends EditRecord
{
    protected static string $resource = WheelResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        $user = auth()->user();
        if ($user && $user->isManager() && $this->record->user_id !== $user->id) {
            abort(403, 'У вас нет доступа к этому колесу');
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
            // Менеджер не может менять user_id
            $data['user_id'] = $this->record->user_id;
        }

        return $data;
    }
}



