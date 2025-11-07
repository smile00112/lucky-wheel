<?php

namespace App\Filament\Resources\WheelResource\RelationManagers;

use App\Filament\Schemas\PrizeSchema;
use App\Models\Prize;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PrizesRelationManager extends RelationManager
{
    protected static string $relationship = 'prizes';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return PrizeSchema::form($schema, includeWheelId: false);
    }

    public function table(Table $table): Table
    {
        return PrizeSchema::table($table, includeWheelColumn: false, includeFullColumns: false)
            ->recordTitleAttribute('name')
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        // Устанавливаем wheel_id автоматически
        $data['wheel_id'] = $this->ownerRecord->id;

        return $this->getRelationship()->create($data);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Защищаем wheel_id от изменения при редактировании
        $data['wheel_id'] = $this->ownerRecord->id;

        $record->update($data);

        return $record;
    }
}

