<?php

namespace App\Filament\Resources\SpinResource\Pages;

use App\Filament\Resources\SpinResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListSpins extends ListRecords
{
    protected static string $resource = SpinResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();
        $user = auth()->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isOwner()) {
            return $query;
        }

        return $query->whereHas('wheel', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });
    }
}




