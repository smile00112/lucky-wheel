<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SpinResource\Pages;
use App\Filament\Schemas\SpinSchema;
use App\Models\Spin;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SpinResource extends Resource
{
    protected static ?string $model = Spin::class;

    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static ?string $navigationLabel = 'Купоны';

    protected static ?string $modelLabel = 'Купон';

    protected static ?string $pluralModelLabel = 'Купоны';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return SpinSchema::form($schema);
    }

    public static function table(Table $table): Table
    {
        return SpinSchema::table($table)
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
            'index' => Pages\ListSpins::route('/'),
            'create' => Pages\CreateSpin::route('/create'),
            'edit' => Pages\EditSpin::route('/{record}/edit'),
        ];
    }
}

