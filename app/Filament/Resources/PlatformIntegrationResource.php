<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlatformIntegrationResource\Pages;
use App\Filament\Schemas\BotWebHookFieldSchema;
use App\Models\PlatformIntegration;
use App\Models\Wheel;
use App\Services\TelegramConnector;
use Filament\Forms;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;

class PlatformIntegrationResource extends Resource
{
    protected static ?string $model = PlatformIntegration::class;

    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?string $navigationLabel = 'Интеграции';

    protected static ?string $modelLabel = 'Интеграция';

    protected static ?string $pluralModelLabel = 'Интеграции';

    protected static ?int $navigationSort = 90;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Toggle::make('is_active')
                    ->label('Активна')
                    ->default(false)
                    ->helperText('Включить интеграцию')
                    ->columnSpanFull()
                ,
                Forms\Components\Select::make('platform')
                    ->label('Платформа')
                    ->options([
                        PlatformIntegration::PLATFORM_TELEGRAM => 'Telegram',
                        PlatformIntegration::PLATFORM_VK => 'VK',
                        PlatformIntegration::PLATFORM_MAX => 'MAX',
                    ])
                    ->required()
                    ->live()
                    ->disabled(fn ($record) => $record !== null)
                    ->helperText('Выберите платформу для интеграции'),
                Forms\Components\Select::make('wheel_id')
                    ->label('Колесо')
                    ->relationship('wheel', 'name', modifyQueryUsing: function ($query) {
                        $user = auth()->user();
                        if ($user && $user->isManager()) {
                            $query->where('user_id', $user->id);
                        }
                        return $query;
                    })
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->helperText('Выберите колесо для интеграции'),
                Forms\Components\TextInput::make('bot_token')
                    ->label('Токен бота')
                    ->maxLength(255)
                    ->helperText('Токен бота от BotFather (для Telegram)'),

                Forms\Components\TextInput::make('bot_username')
                    ->label('Имя бота')
                    ->maxLength(255)
                    ->hidden(fn ($record) => $record &&  $record->platform === PlatformIntegration::PLATFORM_VK ? true : false )
                    ->live()
                    ->helperText('Имя бота (например, @my_bot)'),

//                Forms\Components\TextInput::make('settings.default_wheel_slug')
//                    ->label('Slug колеса по умолчанию')
//                    ->helperText('Slug колеса, которое будет использоваться для запуска из бота')
//                    ->visible(fn ($record) => $record && $record->platform === PlatformIntegration::PLATFORM_TELEGRAM),

//                Forms\Components\KeyValue::make('settings')
//                    ->label('Дополнительные настройки')
//                    ->helperText('Дополнительные параметры для платформы (JSON)')
//                    ->columnSpanFull(),

                Section::make('Настройки фраз бота')
                    ->description('')
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed(true)
                    ->schema([
                        Forms\Components\KeyValue::make('words_settings')
                            ->label('Тексты сообщений и кнопок')
                            ->columnSpanFull()
                            ->helperText('* Доступные переменные:
                                             * - {wheel_name} - название колеса
                                             * - {wheel_description} - описание колеса
                                             * - {wheel_slug} - slug колеса
                                             * - {wheel_company_name} - название компании
                                             * - {prize_name} - название приза
                                             * - {prize_full_name} - полное название приза
                                             * - {prize_mobile_name} - мобильное название приза
                                             * - {prize_description} - описание приза
                                             * - {prize_text_for_winner} - текст для победителя
                                             * - {prize_value} - значение приза
                                             * - {prize_type} - тип приза
                                             * - {code} - код для получения приза
                                             * - {prize_email_image} - ссылка на qr код приза
                                             * - {prize_date} - дата получения приза
                                             '
                            )
                            //->visible(fn ($record) => !$record || $record->platform === PlatformIntegration::PLATFORM_TELEGRAM)
                            ->afterStateHydrated(function ($component, $state, $record) {
                                if ($record !== null && (empty($record->words_settings) || !is_array($state))) {
                                    if($record->platform === PlatformIntegration::PLATFORM_TELEGRAM)
                                        $component->state(PlatformIntegration::getDefaultTelegramSettings());
                                    if($record->platform === PlatformIntegration::PLATFORM_VK)
                                        $component->state(PlatformIntegration::getDefaultVkSettings());
                                }
                            }),
                ]),
            Section::make('Дополнительные настройки бота')
                //->description('')
                ->description(fn ($record) => $record &&  $record->platform === PlatformIntegration::PLATFORM_VK ? 'Дополнительные параметры для платформы.ВНИМАНИЕ! Обязательно заполните поля "Код для верификации хука VK" и Ссылку на мини приложение VK' : 'Дополнительные параметры для платформы')

                ->columnSpanFull()
                ->collapsible()
                ->collapsed(true)
                ->schema([
                    Forms\Components\Repeater::make('settings')
                        ->label('')
                        //->helperText(fn ($record) => $record &&  $record->platform === PlatformIntegration::PLATFORM_VK ? 'Дополнительные параметры для платформы.ВНИМАНИЕ! Обязательно заполните поля "Код для верификации хука VK" и Ссылку на мини приложение VK' : 'Дополнительные параметры для платформы')
                        ->schema([
                            Forms\Components\Select::make('key')
                                ->label('Ключ')
                                ->options([
                                    'hook_verification_code' => 'Код для верификации хука VK',
                                    'app_id' => 'id Мини-приложения в VK',
                                ])
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(fn ($state, $set) => $state !== 'custom' ? $set('custom_key', null) : null),
                            Forms\Components\TextInput::make('custom_key')
                                ->label('Свой ключ')
                                ->visible(fn ($get) => $get('key') === 'custom')
                                ->required(fn ($get) => $get('key') === 'custom')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('value')
                                ->label('Значение')
                                ->required()
                                ->maxLength(255),
                        ])
                        ->defaultItems(0)
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['key'] === 'custom' ? ($state['custom_key'] ?? null) : ($state['key'] ?? null))
                        ->columnSpanFull()
                    // ->hidden()
                    ,
                ]),

                BotWebHookFieldSchema::field()


            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('platform')
                    ->label('Платформа')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        PlatformIntegration::PLATFORM_TELEGRAM => 'Telegram',
                        PlatformIntegration::PLATFORM_VK => 'VK',
                        PlatformIntegration::PLATFORM_MAX => 'MAX',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match($state) {
                        PlatformIntegration::PLATFORM_TELEGRAM => 'info',
                        PlatformIntegration::PLATFORM_VK => 'success',
                        PlatformIntegration::PLATFORM_MAX => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('bot_username')
                    ->label('Имя бота')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('wheel.name')
                    ->label('Колесо')
                    ->searchable()
                    ->sortable()
                    ->default('—'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активна')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('webhook_url')
                    ->label('Webhook URL')
                    ->limit(50)
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Обновлено')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активна')
                    ->placeholder('Все')
                    ->trueLabel('Активные')
                    ->falseLabel('Неактивные'),
            ])
            ->actions([
                EditAction::make(),
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
            'index' => Pages\ListPlatformIntegrations::route('/'),
            'create' => Pages\CreatePlatformIntegration::route('/create'),
            'edit' => Pages\EditPlatformIntegration::route('/{record}/edit'),
        ];
    }
}

