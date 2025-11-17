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
use Filament\Tables\Filters\Filter;
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
            ->filters([
                Filter::make('spins_date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('spins_created_from')
                            ->label('Дата начала'),
                        \Filament\Forms\Components\DatePicker::make('spins_created_until')
                            ->label('Дата окончания'),
                    ])
                    ->query(function ($query, array $data) {
                        // Фильтр применяется к запросу призов, но не влияет напрямую на вычисляемые поля
                        // Реальная фильтрация будет в getStateUsing колонок
                        return $query;
                    }),
            ])
            ->actions([
                EditAction::make()->iconButton(),
                DeleteAction::make()->iconButton(),
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

