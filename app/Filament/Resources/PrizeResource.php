<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PrizeResource\Pages;
use App\Models\Prize;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PrizeResource extends Resource
{
    protected static ?string $model = Prize::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationLabel = 'Призы';

    protected static ?string $modelLabel = 'Приз';

    protected static ?string $pluralModelLabel = 'Призы';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('wheel_id')
                    ->label(__('filament.prize.wheel_id'))
                    ->relationship('wheel', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn () => null),
                Forms\Components\TextInput::make('name')
                    ->label(__('filament.prize.name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label(__('filament.prize.description'))
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('text_for_winner')
                    ->label(__('filament.prize.text_for_winner'))
                    ->rows(2)
                    ->helperText(__('filament.prize.text_for_winner_hint'))
                    ->columnSpanFull(),
                Forms\Components\Select::make('type')
                    ->label(__('filament.prize.type'))
                    ->options([
                        'discount' => __('filament.prize.type_discount'),
                        'free_item' => __('filament.prize.type_free_item'),
                        'cash' => __('filament.prize.type_cash'),
                        'points' => __('filament.prize.type_points'),
                        'other' => __('filament.prize.type_other'),
                    ])
                    ->default('other')
                    ->required(),
                Forms\Components\TextInput::make('value')
                    ->label(__('filament.prize.value'))
                    ->maxLength(255)
                    ->helperText(__('filament.prize.value_hint')),
                Forms\Components\TextInput::make('probability')
                    ->label(__('filament.prize.probability'))
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->maxValue(100)
                    ->step(0.01)
                    ->suffix('%')
                    ->helperText(__('filament.prize.probability_hint'))
                    ->required(),
                Forms\Components\FileUpload::make('image')
                    ->label(__('filament.prize.image'))
                    ->image()
                    ->directory('prizes')
                    ->visibility('public')
                    ->columnSpanFull(),
                Forms\Components\ColorPicker::make('color')
                    ->label(__('filament.prize.color'))
                    ->helperText(__('filament.prize.color_hint')),
                Forms\Components\Toggle::make('is_active')
                    ->label(__('filament.prize.is_active'))
                    ->default(true),
                Forms\Components\TextInput::make('sort')
                    ->label(__('filament.prize.sort'))
                    ->numeric()
                    ->default(0)
                    ->required(),
                Forms\Components\TextInput::make('quantity_limit')
                    ->label(__('filament.prize.quantity_limit'))
                    ->numeric()
                    ->minValue(1)
                    ->helperText(__('filament.prize.quantity_limit_hint')),
                Forms\Components\TextInput::make('quantity_day_limit')
                    ->label(__('filament.prize.quantity_day_limit'))
                    ->numeric()
                    ->minValue(1)
                    ->helperText(__('filament.prize.quantity_day_limit_hint')),
                Forms\Components\TextInput::make('quantity_guest_limit')
                    ->label(__('filament.prize.quantity_guest_limit'))
                    ->numeric()
                    ->minValue(1)
                    ->helperText(__('filament.prize.quantity_guest_limit_hint')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label(__('filament.prize.image'))
                    ->circular()
                    ->defaultImageUrl(asset('images/logo.png')),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('filament.prize.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('wheel.name')
                    ->label(__('filament.prize.wheel_id'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('filament.prize.type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'discount' => __('filament.prize.type_discount'),
                        'free_item' => __('filament.prize.type_free_item'),
                        'cash' => __('filament.prize.type_cash'),
                        'points' => __('filament.prize.type_points'),
                        'other' => __('filament.prize.type_other'),
                        default => $state,
                    })
                    ->color(fn (string $state): string => match($state) {
                        'discount' => 'success',
                        'free_item' => 'info',
                        'cash' => 'warning',
                        'points' => 'primary',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('value')
                    ->label(__('filament.prize.value'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('probability')
                    ->label(__('filament.prize.probability'))
                    ->numeric(decimalPlaces: 2)
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sort')
                    ->label(__('filament.prize.sort'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('filament.prize.is_active'))
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity_used')
                    ->label(__('filament.prize.quantity_used'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity_limit')
                    ->label(__('filament.prize.quantity_limit'))
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ?? __('filament.prize.unlimited')),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament.prize.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('wheel_id')
                    ->label(__('filament.prize.wheel_id'))
                    ->relationship('wheel', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('filament.prize.type'))
                    ->options([
                        'discount' => __('filament.prize.type_discount'),
                        'free_item' => __('filament.prize.type_free_item'),
                        'cash' => __('filament.prize.type_cash'),
                        'points' => __('filament.prize.type_points'),
                        'other' => __('filament.prize.type_other'),
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('filament.prize.is_active'))
                    ->placeholder(__('filament.all'))
                    ->trueLabel(__('filament.active'))
                    ->falseLabel(__('filament.inactive'))
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort');
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

