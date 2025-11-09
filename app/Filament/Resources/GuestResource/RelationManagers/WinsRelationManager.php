<?php

namespace App\Filament\Resources\GuestResource\RelationManagers;

use App\Filament\Schemas\SpinSchema;
use App\Models\Spin;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class WinsRelationManager extends RelationManager
{
    protected static string $relationship = 'wins';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $title = 'Выигрыши';

    public function form(Schema $schema): Schema
    {
        return SpinSchema::form(
            $schema,
            includeWheelId: true,
            includeGuestId: false, // guest_id будет установлен автоматически
            includePrizeId: true
        );
    }

    public function table(Table $table): Table
    {
        return SpinSchema::table(
            $table,
            includeWheelColumn: true,
            includeGuestColumn: false, // guest уже известен
            includeFullColumns: true
        )
            ->recordTitleAttribute('id')
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('prize_id'));
    }

    protected function handleRecordCreation(array $data): Model
    {
        // Устанавливаем guest_id автоматически
        $data['guest_id'] = $this->ownerRecord->id;
        
        // Убеждаемся, что prize_id установлен (выигрыш должен иметь приз)
        if (empty($data['prize_id'])) {
            throw new \Exception('Выигрыш должен иметь приз');
        }

        return $this->getRelationship()->create($data);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Защищаем guest_id от изменения при редактировании
        $data['guest_id'] = $this->ownerRecord->id;
        
        // Убеждаемся, что prize_id установлен
        if (empty($data['prize_id'])) {
            throw new \Exception('Выигрыш должен иметь приз');
        }

        $record->update($data);

        return $record;
    }
}

