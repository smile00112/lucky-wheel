<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->id !== auth()->id()),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user = auth()->user();

        if ($user && $user->isOwner() && isset($data['role']) && $data['role'] === \App\Models\User::ROLE_MANAGER) {
            $data['company_id'] = $user->company_id;
        }

        if (isset($data['owner_id']) && $data['owner_id']) {
            $owner = \App\Models\User::find($data['owner_id']);
            if ($owner && $owner->company_id) {
                $data['company_id'] = $owner->company_id;
            }
        }

        return $data;
    }
}

