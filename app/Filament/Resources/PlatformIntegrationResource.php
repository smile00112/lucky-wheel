<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlatformIntegrationResource\Pages;
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
                    ->unique(ignoreRecord: true)
                    ->disabled(fn ($record) => $record !== null)
                    ->helperText('Выберите платформу для интеграции'),
                Forms\Components\Select::make('wheel_id')
                    ->label('Колесо')
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
                    ->helperText('Имя бота (например, @my_bot)'),

//                Forms\Components\TextInput::make('settings.default_wheel_slug')
//                    ->label('Slug колеса по умолчанию')
//                    ->helperText('Slug колеса, которое будет использоваться для запуска из бота')
//                    ->visible(fn ($record) => $record && $record->platform === PlatformIntegration::PLATFORM_TELEGRAM),

//                Forms\Components\KeyValue::make('settings')
//                    ->label('Дополнительные настройки')
//                    ->helperText('Дополнительные параметры для платформы (JSON)')
//                    ->columnSpanFull(),

                Forms\Components\Repeater::make('settings')
                    ->label('Дополнительные настройки')
                    ->helperText('Дополнительные параметры для платформы')
                    ->schema([
                        Forms\Components\Select::make('key')
                            ->label('Ключ')
                            ->options([
                                'welcome message' => 'Приветствие',
                                '' => 'Телефон',
                                'name' => 'Имя',
                                'ip_address' => 'IP адрес',
                                'user_agent' => 'User Agent',
                                'metadata' => 'Метаданные',
                                'custom' => 'Своё поле',
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
                    ->hidden(),

                Section::make('Настройки фраз бота')
                    ->description('')
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed(true)
                    ->schema([
                        Forms\Components\KeyValue::make('words_settings')
                            ->label('Тексты сообщений и кнопок')
                            ->columnSpanFull()
                            ->helperText('Настройки текстов сообщений и кнопок для Telegram')
                            ->visible(fn ($record) => !$record || $record->platform === PlatformIntegration::PLATFORM_TELEGRAM)
                            ->afterStateHydrated(function ($component, $state, $record) {
                                if ($record === null && (empty($state) || !is_array($state))) {
                                    $component->state(PlatformIntegration::getDefaultTelegramSettings());
                                }
                            }),
                ]),

                Section::make('Webhook')
                    ->schema([
                        Forms\Components\TextInput::make('webhook_url')
                            ->label('URL вебхука')
                            ->disabled()
                            ->dehydrated(false)
                            ->afterStateHydrated(function ($component, $record) {
                                if (!$record) {
                                    return;
                                }
                                $baseUrl = config('app.url');
                                $url = $baseUrl . '/telegram/' . $record->id . '/webhook';
                                $component->state($url);
                            })
                            ->helperText('URL для регистрации вебхука'),
                        Actions::make([
                            Action::make('register_webhook')
                                ->label('Зарегистрировать вебхук')
                                ->icon('heroicon-o-link')
                                ->color('success')
                                ->action(function ($record) {
                                    if (!$record || !$record->bot_token) {
                                        throw new \Exception('Токен бота не указан');
                                    }

                                    $baseUrl = config('app.url');
                                    $webhookUrl = $baseUrl . '/telegram/' . $record->id . '/webhook';

                                    $connector = new TelegramConnector();
                                    $result = $connector->registerWebhook($record, $webhookUrl);

                                    if ($result) {
                                        \Filament\Notifications\Notification::make()
                                            ->title('Вебхук успешно зарегистрирован')
                                            ->success()
                                            ->send();
                                    } else {
                                        \Filament\Notifications\Notification::make()
                                            ->title('Не удалось зарегистрировать вебхук')
                                            ->danger()
                                            ->send();
                                        //throw new \Exception('Не удалось зарегистрировать вебхук');
                                    }
                                })
                                ->requiresConfirmation()
                                ->modalHeading('Регистрация вебхука')
                                ->modalDescription('Вы уверены, что хотите зарегистрировать вебхук для этого бота?')
                                ->visible(fn ($record) => $record && $record->platform === PlatformIntegration::PLATFORM_TELEGRAM),
                        ]),
                    ])
                    ->collapsible()
                    ->visible(fn ($record) => $record && $record->platform === PlatformIntegration::PLATFORM_TELEGRAM),
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

