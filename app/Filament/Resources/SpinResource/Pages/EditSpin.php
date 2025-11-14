<?php

namespace App\Filament\Resources\SpinResource\Pages;

use App\Filament\Resources\SpinResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSpin extends EditRecord
{
    protected static string $resource = SpinResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        $user = auth()->user();
        if ($user && $user->isManager() && $this->record->wheel->user_id !== $user->id) {
            abort(403, 'У вас нет доступа к этому купону');
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
            
            // Проверяем, что prize_id принадлежит колесу менеджера
            if (isset($data['prize_id']) && $data['prize_id']) {
                $prize = \App\Models\Prize::find($data['prize_id']);
                if ($prize && $prize->wheel->user_id !== $user->id) {
                    $data['prize_id'] = $this->record->prize_id;
                }
            }
        }

        return $data;
    }
}




