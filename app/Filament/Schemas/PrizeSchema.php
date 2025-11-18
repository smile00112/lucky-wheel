<?php

namespace App\Filament\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class PrizeSchema
{
    /**
     * Получить схему формы для приза
     *
     * @param Schema $schema
     * @param bool $includeWheelId Включить ли поле wheel_id (для ресурса приза)
     * @param bool $hideType Скрыть ли поле type
     * @param bool $hideValue Скрыть ли поле value
     * @return Schema
     */
    public static function form(Schema $schema, bool $includeWheelId = false, bool $hideType = false, bool $hideValue = false): Schema
    {
        $components = [];

        if ($includeWheelId) {
            $components[] = Forms\Components\Select::make('wheel_id')
                ->label(__('filament.prize.wheel_id'))
                ->relationship('wheel', 'name')
                ->required()
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(fn () => null)
                ->columnSpanFull()
            ;
        }

        $components = array_merge($components, [
            Forms\Components\Toggle::make('is_active')
                ->label(__('filament.prize.is_active'))
                ->default(true)
                ->columnSpanFull()
            ,
            Forms\Components\TextInput::make('name')
                ->label(__('filament.prize.name'))
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('value')
                ->label(__('filament.prize.value'))
                ->maxLength(255)
                ->helperText(__('filament.prize.value_hint'))
                ->hidden($hideValue),
            Forms\Components\Textarea::make('description')
                ->label(__('filament.prize.description'))
                ->rows(3)
                //->columnSpanFull()
            ,
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
                ->hidden(),

            Forms\Components\TextInput::make('probability')
                ->label(__('filament.prize.probability'))
                ->numeric()
                ->default(0)
                ->minValue(0)
                ->maxValue(100)
                ->step(0.01)
                ->suffix('%')
                ->helperText(__('filament.prize.probability_hint'))
                //->required()
                ->columnSpanFull()
                ,
            Forms\Components\FileUpload::make('image')
                ->label(__('filament.prize.image'))
                ->image()
                ->disk('public')  // Добавьте эту строку
                ->directory('prizes')
                ->visibility('public')
                ->columns(1),
            Forms\Components\FileUpload::make('email_image')
                ->label(__('filament.prize.email_image'))
                ->image()
                ->disk('public')  // Добавьте эту строку
                ->directory('prizes')
                ->visibility('public')
                ->columns(1),
            Forms\Components\ColorPicker::make('color')
                ->label(__('filament.prize.color'))
                ->helperText(__('filament.prize.color_hint')),

            Forms\Components\TextInput::make('sort')
                ->label(__('filament.prize.sort'))
                ->numeric()
                ->default(0)
                ->required()
                ->hidden(),
            Forms\Components\TextInput::make('quantity_limit')
                ->label(__('filament.prize.quantity_limit'))
                ->numeric()
                ->minValue(1)
                ->helperText(__('filament.prize.quantity_limit_hint'))
                ,
            Forms\Components\TextInput::make('quantity_day_limit')
                ->label(__('filament.prize.quantity_day_limit'))
                ->numeric()
                ->minValue(1)
                ->helperText(__('filament.prize.quantity_day_limit_hint'))
                ->hidden(),
            Forms\Components\TextInput::make('quantity_guest_limit')
                ->label(__('filament.prize.quantity_guest_limit'))
                ->numeric()
                ->minValue(1)
                ->helperText(__('filament.prize.quantity_guest_limit_hint'))
                ->hidden(),
        ]);

        return $schema->components($components);
    }

    /**
     * Получить схему таблицы для приза
     *
     * @param Table $table
     * @param bool $includeWheelColumn Включить ли колонку wheel (для ресурса приза)
     * @param bool $includeFullColumns Включить ли все колонки (type, value, probability, sort)
     * @param array $additionalColumns Дополнительные колонки для добавления
     * @return Table
     */
    public static function table(Table $table, bool $includeWheelColumn = false, bool $includeFullColumns = true, array $additionalColumns = []): Table
    {
        $columns = [
            Tables\Columns\ImageColumn::make('image')
                ->label('')
                ->disk('public')  // Добавьте эту строку
                ->visibility('public')
                ->circular()
                ->defaultImageUrl(asset('images/logo.png')),
            Tables\Columns\TextColumn::make('name')
                ->label(__('filament.prize.name'))
                ->searchable()
                ->sortable(),
        ];

        if ($includeWheelColumn) {
            $columns[] = Tables\Columns\TextColumn::make('wheel.name')
                ->label(__('filament.prize.wheel_id'))
                ->searchable()
                ->sortable();
        }

        if ($includeFullColumns) {
            $columns = array_merge($columns, [
//                Tables\Columns\TextColumn::make('type')
//                    ->label(__('filament.prize.type'))
//                    ->badge()
//                    ->formatStateUsing(fn (string $state): string => match($state) {
//                        'discount' => __('filament.prize.type_discount'),
//                        'free_item' => __('filament.prize.type_free_item'),
//                        'cash' => __('filament.prize.type_cash'),
//                        'points' => __('filament.prize.type_points'),
//                        'other' => __('filament.prize.type_other'),
//                        default => $state,
//                    })
//                    ->color(fn (string $state): string => match($state) {
//                        'discount' => 'success',
//                        'free_item' => 'info',
//                        'cash' => 'warning',
//                        'points' => 'primary',
//                        default => 'gray',
//                    }),
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
            ]);
        }

        $columns = array_merge($columns, [
            Tables\Columns\IconColumn::make('is_active')
                ->label(__('filament.prize.is_active'))
                ->boolean()
                ->sortable(),
            Tables\Columns\TextColumn::make('wins')
                ->label(__('filament.prize.quantity_wins'))
                ->numeric()
                ->getStateUsing(function ($record, $livewire) {
                    $user = auth()->user();
                    $query = $record->spins();

                    // Фильтрация по ролям (как в виджетах)
                    if ($user && $user->isManager()) {
                        $query->whereHas('wheel', function ($q) use ($user) {
                            $q->where('user_id', $user->id);
                        });
                    }

                    // Фильтрация по датам из фильтра таблицы
                    $filters = $livewire->tableFilters ?? [];
                    $dateFilter = $filters['spins_date'] ?? null;

                    if ($dateFilter) {
                        if (isset($dateFilter['spins_created_from'])) {
                            $query->whereDate('created_at', '>=', $dateFilter['spins_created_from']);
                        }
                        if (isset($dateFilter['spins_created_until'])) {
                            $query->whereDate('created_at', '<=', $dateFilter['spins_created_until']);
                        }
                    }

                    return $query->count();
                }),
            Tables\Columns\TextColumn::make('used')
                ->label(__('filament.prize.quantity_used'))
                ->numeric()
                ->getStateUsing(function ($record, $livewire) {
                    $user = auth()->user();
                    $query = $record->spins()->where('status', 'claimed');

                    // Фильтрация по ролям (как в виджетах)
                    if ($user && $user->isManager()) {
                        $query->whereHas('wheel', function ($q) use ($user) {
                            $q->where('user_id', $user->id);
                        });
                    }

                    // Фильтрация по датам из фильтра таблицы
                    $filters = $livewire->tableFilters ?? [];
                    $dateFilter = $filters['spins_date'] ?? null;

                    if ($dateFilter) {
                        if (isset($dateFilter['spins_created_from'])) {
                            $query->whereDate('created_at', '>=', $dateFilter['spins_created_from']);
                        }
                        if (isset($dateFilter['spins_created_until'])) {
                            $query->whereDate('created_at', '<=', $dateFilter['spins_created_until']);
                        }
                    }

                    return $query->count();
                }),
            Tables\Columns\TextColumn::make('quantity_limit')
                ->label(__('filament.prize.quantity_limit'))
                ->numeric()
                ->default('-')
                ->sortable()
                ->formatStateUsing(fn ($state) => $state ?? __('filament.prize.unlimited')),
        ]);

        // Добавляем дополнительные колонки, если они переданы
        if (!empty($additionalColumns)) {
            $columns = array_merge($columns, $additionalColumns);
        }

        $filters = [
//            Tables\Filters\SelectFilter::make('type')
//                ->label(__('filament.prize.type'))
//                ->options([
//                    'discount' => __('filament.prize.type_discount'),
//                    'free_item' => __('filament.prize.type_free_item'),
//                    'cash' => __('filament.prize.type_cash'),
//                    'points' => __('filament.prize.type_points'),
//                    'other' => __('filament.prize.type_other'),
//                ]),
            Tables\Filters\TernaryFilter::make('is_active')
                ->label(__('filament.prize.is_active'))
                ->placeholder(__('filament.all'))
                ->trueLabel(__('filament.active'))
                ->falseLabel(__('filament.inactive'))
                ->native(false),
        ];

        if ($includeWheelColumn) {
            $filters[] = Tables\Filters\SelectFilter::make('wheel_id')
                ->label(__('filament.prize.wheel_id'))
                ->relationship('wheel', 'name')
                ->searchable()
                ->preload();
        }

        return $table
            ->columns($columns)
            ->filters($filters)
            ->defaultSort('sort');
    }
}

