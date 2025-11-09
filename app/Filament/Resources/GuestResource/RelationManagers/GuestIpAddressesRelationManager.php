<?php

namespace App\Filament\Resources\GuestResource\RelationManagers;

use App\Models\GuestIpAddress;
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

class GuestIpAddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'ipAddresses';

    protected static ?string $recordTitleAttribute = 'ip_address';

    protected static ?string $title = 'IP-адреса';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('ip_address')
                    ->label(__('filament.guest_ip_address.ip_address'))
                    ->required()
                    ->maxLength(45)
                    ->ip(),
                Forms\Components\Textarea::make('user_agent')
                    ->label(__('filament.guest_ip_address.user_agent'))
                    ->rows(2)
                    ->columnSpanFull(),
                Forms\Components\KeyValue::make('metadata')
                    ->label(__('filament.guest_ip_address.metadata'))
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('ip_address')
            ->columns([
                Tables\Columns\TextColumn::make('ip_address')
                    ->label(__('filament.guest_ip_address.ip_address'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user_agent')
                    ->label(__('filament.guest_ip_address.user_agent'))
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->user_agent)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament.guest_ip_address.created_at'))
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('filament.guest_ip_address.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //CreateAction::make(),
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
        // Устанавливаем guest_id автоматически
        $data['guest_id'] = $this->ownerRecord->id;

        return $this->getRelationship()->create($data);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Защищаем guest_id от изменения при редактировании
        $data['guest_id'] = $this->ownerRecord->id;

        $record->update($data);

        return $record;
    }
}

