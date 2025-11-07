<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PrizeResource\Pages;
use App\Filament\Schemas\PrizeSchema;
use App\Models\Prize;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PrizeResource extends Resource
{
    protected static ?string $model = Prize::class;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedGift;

    protected static ?string $navigationLabel = 'Призы';

    protected static ?string $modelLabel = 'Приз';

    protected static ?string $pluralModelLabel = 'Призов';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return PrizeSchema::form($schema, includeWheelId: true, hideType: true, hideValue: true);
    }

    public static function table(Table $table): Table
    {
        return PrizeSchema::table(
            $table, 
            includeWheelColumn: true, 
            includeFullColumns: false,
            additionalColumns: [
                \Filament\Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament.prize.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ]
        )
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPrizes::route('/'),
            'create' => Pages\CreatePrize::route('/create'),
            'edit' => Pages\EditPrize::route('/{record}/edit'),
        ];
    }
}

