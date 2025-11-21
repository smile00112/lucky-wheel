<?php

namespace App\Filament\Resources\GuestResource\RelationManagers;

use App\Models\TelegramUser;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TelegramUserRelationManager extends RelationManager
{
    protected static string $relationship = 'telegramUser';

    protected static ?string $recordTitleAttribute = 'username';

    protected static ?string $title = 'Telegram пользователь';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('telegram_id')
                    ->label(__('filament.telegram_user.telegram_id'))
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('username')
                    ->label(__('filament.telegram_user.username'))
                    ->maxLength(255),
                Forms\Components\TextInput::make('first_name')
                    ->label(__('filament.telegram_user.first_name'))
                    ->maxLength(255),
                Forms\Components\TextInput::make('last_name')
                    ->label(__('filament.telegram_user.last_name'))
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->label(__('filament.telegram_user.phone'))
                    ->tel()
                    ->maxLength(255),
                Forms\Components\KeyValue::make('metadata')
                    ->label(__('filament.telegram_user.metadata'))
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('username')
            ->columns([
                Tables\Columns\TextColumn::make('telegram_id')
                    ->label(__('filament.telegram_user.telegram_id'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('username')
                    ->label(__('filament.telegram_user.username'))
                    ->searchable()
                    ->sortable()
                    ->default('-'),
                Tables\Columns\TextColumn::make('first_name')
                    ->label(__('filament.telegram_user.first_name'))
                    ->searchable()
                    ->sortable()
                    ->default('-'),
                Tables\Columns\TextColumn::make('last_name')
                    ->label(__('filament.telegram_user.last_name'))
                    ->searchable()
                    ->sortable()
                    ->default('-'),
                Tables\Columns\TextColumn::make('phone')
                    ->label(__('filament.telegram_user.phone'))
                    ->searchable()
                    ->sortable()
                    ->default('-'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament.telegram_user.created_at'))
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('filament.telegram_user.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
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
            ->defaultSort('created_at', 'desc');
    }

    protected function handleRecordCreation(array $data): Model
    {
        $data['guest_id'] = $this->ownerRecord->id;

        return $this->getRelationship()->create($data);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $data['guest_id'] = $this->ownerRecord->id;

        $record->update($data);

        return $record;
    }
}

