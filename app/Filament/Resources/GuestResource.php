<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GuestResource\Pages;
use App\Models\Guest;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;

class GuestResource extends Resource
{
    protected static ?string $model = Guest::class;

    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedUser;

    protected static ?string $navigationLabel = 'Гости';

    protected static ?string $modelLabel = 'Гость';

    protected static ?string $pluralModelLabel = 'Гости';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label(__('filament.guest.name'))
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->label(__('filament.guest.email'))
                    ->email()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->label(__('filament.guest.phone'))
                    ->tel()
                    ->maxLength(255),
                Forms\Components\TextInput::make('ip_address')
                    ->label(__('filament.guest.ip_address'))
                    ->maxLength(45),
                Forms\Components\Textarea::make('user_agent')
                    ->label(__('filament.guest.user_agent'))
                    ->rows(2)
                    ->columnSpanFull(),
                Forms\Components\KeyValue::make('metadata')
                    ->label(__('filament.guest.metadata'))
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label(__('filament.guest.id'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('filament.guest.name'))
                    ->searchable()
                    ->sortable()
                    ->default('-'),
                Tables\Columns\TextColumn::make('email')
                    ->label(__('filament.guest.email'))
                    ->searchable()
                    ->sortable()
                    ->default('-'),
                Tables\Columns\TextColumn::make('phone')
                    ->label(__('filament.guest.phone'))
                    ->searchable()
                    ->sortable()
                    ->default('-'),
                Tables\Columns\TextColumn::make('spins_count')
                    ->label(__('filament.guest.spins_count'))
                    ->counts('spins')
                    ->sortable(),
//                Tables\Columns\TextColumn::make('wins_count')
//                    ->label(__('filament.guest.wins_count'))
//                    ->counts('wins')
//                    ->sortable()
//                    ->toggleable(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label(__('filament.guest.ip_address'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament.guest.created_at'))
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('filament.guest.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_email')
                    ->label(__('filament.guest.has_email'))
                    ->query(fn ($query) => $query->whereNotNull('email'))
                    ->toggle(),
                Tables\Filters\Filter::make('has_phone')
                    ->label(__('filament.guest.has_phone'))
                    ->query(fn ($query) => $query->whereNotNull('phone'))
                    ->toggle(),
                Tables\Filters\Filter::make('has_name')
                    ->label(__('filament.guest.has_name'))
                    ->query(fn ($query) => $query->whereNotNull('name'))
                    ->toggle(),
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

    public static function getRelations(): array
    {
        return [
            GuestResource\RelationManagers\WinsRelationManager::class,
            GuestResource\RelationManagers\GuestIpAddressesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGuests::route('/'),
            'create' => Pages\CreateGuest::route('/create'),
            'edit' => Pages\EditGuest::route('/{record}/edit'),
        ];
    }
}

