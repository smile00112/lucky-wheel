<?php

namespace App\Filament\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Components\Section;

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
                ->relationship('wheel', 'name', modifyQueryUsing: function ($query) {
                    $user = auth()->user();
                    if (!$user || $user->isAdmin()) {
                        return $query;
                    }
                    $companyId = $user->company_id;
                    if (!$companyId) {
                        return $query->whereRaw('1 = 0');
                    }
                    return $query->whereHas('user', function ($q) use ($companyId) {
                        $q->where('company_id', $companyId);
                    });
                })
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
                ->helperText(__('filament.prize.name_hint'))
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('mobile_name')
                ->label(__('filament.prize.mobile_name'))
                ->helperText(__('filament.prize.mobile_name_hint'))
                ->maxLength(255)
                ,
            Forms\Components\Select::make('sector_view')
                ->label(__('filament.prize.sector_view'))
                ->helperText(__('filament.prize.sector_view_hint'))
                ->options([
                    'text_with_image' => __('filament.prize.sector_view_text_with_image'),
                    'only_image' => __('filament.prize.sector_view_only_image'),
                    'only_text' => __('filament.prize.sector_view_only_text'),
                ])
                ->default('text_with_image')
                ->required()
                ->columnSpanFull()
                ->hidden(),
            Forms\Components\TextInput::make('value')
                ->label(__('filament.prize.value'))
                ->maxLength(255)
                ->helperText(__('filament.prize.value_hint'))
                ,
            Forms\Components\Textarea::make('description')
                ->label(__('filament.prize.description'))
                ->helperText(__('filament.prize.description_hint'))
                ->rows(3)
                ->columnSpanFull()
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
            Forms\Components\Toggle::make('use_gradient')
                ->label('Использовать градиент')
                ->default(false)
                ->live()
                ->columnSpanFull(),
            Forms\Components\ColorPicker::make('color')
                ->label(__('filament.prize.color'))
                ->helperText(__('filament.prize.color_hint'))
                ->visible(fn ($get) => !$get('use_gradient')),
            Forms\Components\ColorPicker::make('gradient_start')
                ->label('Цвет начала градиента')
                ->helperText('Цвет в центре секции')
                ->visible(fn ($get) => $get('use_gradient')),
            Forms\Components\ColorPicker::make('gradient_end')
                ->label('Цвет конца градиента')
                ->helperText('Цвет на краю секции')
                ->visible(fn ($get) => $get('use_gradient')),
            Forms\Components\ColorPicker::make('text_color')
                ->label('Цвет текста в секции')
                ->helperText('Цвет текста приза на колесе (по умолчанию белый)')
                ->default('#ffffff'),
            Forms\Components\TextInput::make('font_size')
                ->label('Размер шрифта')
                ->numeric()
                ->minValue(8)
                ->maxValue(72)
                ->helperText('Размер шрифта текста приза на колесе (в пикселях. По умолчанию - 18px)')
                ->default(18),

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
            Section::make('Данные для email')
                ->description('Настройки для отправки приза по email')
                ->columnSpanFull()
                ->collapsible()
                ->collapsed(false)
                ->schema([
                    Forms\Components\TextInput::make('email_name')
                        ->label('Название приза для email')
                        ->maxLength(255)
                        ->helperText('Название приза, которое будет использоваться в email')
                        ->required()
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('email_text_after_congratulation')
                        ->label('Текст после поздравления')
                        ->rows(3)
                        ->helperText('Текст, который будет отображаться после поздравления в email')
                        ->required()
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('email_coupon_after_code_text')
                        ->label('Текст поля кода купона')
                        ->rows(3)
                        ->helperText('Текст, который будет отображаться после кода купона в email')
                        ->required()
                        ->columnSpanFull(),
                ]),
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
//                Tables\Columns\TextColumn::make('value')
//                    ->label(__('filament.prize.value'))
//                    ->searchable(),
//                Tables\Columns\TextColumn::make('probability')
//                    ->label(__('filament.prize.probability'))
//                    ->numeric(decimalPlaces: 2)
//                    ->suffix('%')
//                    ->sortable(),
//                Tables\Columns\TextColumn::make('sort')
//                    ->label(__('filament.prize.sort'))
//                    ->numeric()
//                    ->sortable(),
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

                    // Фильтрация по компании
                    if ($user && !$user->isAdmin()) {
                        $companyId = $user->company_id;
                        if ($companyId) {
                            $query->whereHas('wheel.user', function ($q) use ($companyId) {
                                $q->where('company_id', $companyId);
                            });
                        } else {
                            $query->whereRaw('1 = 0');
                        }
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

                    // Фильтрация по компании
                    if ($user && !$user->isAdmin()) {
                        $companyId = $user->company_id;
                        if ($companyId) {
                            $query->whereHas('wheel.user', function ($q) use ($companyId) {
                                $q->where('company_id', $companyId);
                            });
                        } else {
                            $query->whereRaw('1 = 0');
                        }
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

            Tables\Columns\TextColumn::make('probability')
                ->label(__('filament.prize.probability_short'))
                ->numeric()
                ->default('-')
                ->sortable()
                ->formatStateUsing(function ($state, $record) {
                    if ($record->wheel && $record->wheel->probability_type === 'weighted') {
                        return $state;
                    }
                    return '-';
                })
            ,
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
                ->relationship('wheel', 'name', modifyQueryUsing: function ($query) {
                    $user = auth()->user();
                    if (!$user || $user->isAdmin()) {
                        return $query;
                    }
                    $companyId = $user->company_id;
                    if (!$companyId) {
                        return $query->whereRaw('1 = 0');
                    }
                    return $query->whereHas('user', function ($q) use ($companyId) {
                        $q->where('company_id', $companyId);
                    });
                })
                ->searchable()
                ->preload();
        }

        return $table
            ->columns($columns)
            ->filters($filters)
            ->defaultSort('sort');
    }
}

