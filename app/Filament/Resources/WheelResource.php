<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WheelResource\Pages;
use App\Models\Wheel;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Components\CodeEditor\Enums\Language;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WheelResource extends Resource
{
    protected static ?string $model = Wheel::class;

    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $navigationLabel = 'Колеса';

    protected static ?string $modelLabel = 'Колесо';

    protected static ?string $pluralModelLabel = 'Колеса';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Hidden::make('user_id'),
                Forms\Components\Toggle::make('is_active')
                    ->label(__('filament.wheel.is_active'))
                    ->default(true)
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('force_data_collection')
                    ->label('Принудительный сбор данных')
                    ->default(false)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('name')
                    ->label(__('filament.wheel.name'))
                    ->required()
                    ->maxLength(255)
                    //->copyable()
                ,
                    //->live(onBlur: true)
                    //->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))),
                Forms\Components\TextInput::make('slug')
                    ->label(__('filament.wheel.slug'))
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->alphaDash(),
                Forms\Components\Textarea::make('description')
                    ->label(__('filament.wheel.description'))
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\FileUpload::make('image')
                    ->label('Изображение')
                    ->image()
                    ->disk('public')
                    ->directory('wheels')
                    ->visibility('public')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('spins_limit')
                    ->label(__('filament.wheel.spins_limit'))
                    ->numeric()
                    ->minValue(1)
                    ->helperText(__('filament.wheel.spins_limit_hint')),
                Forms\Components\TextInput::make('refresh_hour')
                    ->label(__('filament.wheel.refresh_hour'))
                    ->type('time')
                    ->helperText(__('filament.wheel.refresh_hour_hint'))
                    ->formatStateUsing(fn ($state) => $state ?: null)
                    ->dehydrateStateUsing(fn ($state) => $state ?: null),
                Forms\Components\Select::make('probability_type')
                    ->label(__('filament.wheel.probability_type'))
                    ->options([
                        'random' => __('filament.wheel.probability_type_random'),
                        'weighted' => __('filament.wheel.probability_type_weighted'),
                    ])
                    ->default('random')
                    ->required()
                    ->helperText(__('filament.wheel.probability_type_hint')),
                Forms\Components\DateTimePicker::make('starts_at')
                    ->label(__('filament.wheel.starts_at'))
                    ->native(false)
                    ,
                Forms\Components\DateTimePicker::make('ends_at')
                    ->label(__('filament.wheel.ends_at'))
                    ->native(false)
                    ,

                Section::make('Настройки текстов')
                    ->description('')
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed(true)
                    ->schema([
                        Forms\Components\KeyValue::make('settings')
                            ->label(__('filament.wheel.settings'))
                            ->columnSpanFull()
                            ->afterStateHydrated(function ($component, $state, $record) {
                                // Заполняем значения по умолчанию только при создании новой записи
                                if (!$record && (empty($state) || !is_array($state))) {
                                    $defaultSettings = [
                                        'loading_text' => 'Загрузка...',
                                        'spin_button_text' => 'Крутить колесо!',
                                        'spin_button_blocked_text' => 'Вы уже выиграли сегодня. Попробуйте завтра!',
                                        'won_prize_label' => 'Выиграно сегодня:',
                                        'win_notification_title' => '🎉 Поздравляем с выигрышем!',
                                        'win_notification_win_text' => 'Вы выиграли:',
                                        'copy_code_button_title' => 'Копировать код',
                                        'code_not_specified' => 'Код не указан',
                                        'download_pdf_text' => 'Скачать сертификат PDF',
                                        'form_description' => 'Для получения приза на почту заполните данные:',
                                        'form_name_placeholder' => 'Ваше имя',
                                        'form_email_placeholder' => 'Email',
                                        'form_phone_placeholder' => '+7 (XXX) XXX-XX-XX',
                                        'form_submit_text' => 'Отправить приз',
                                        'form_submit_loading' => 'Отправка...',
                                        'form_submit_success' => '✓ Приз отправлен!',
                                        'form_submit_error' => 'Приз уже получен',
                                        'form_success_message' => '✓ Данные сохранены! Приз будет отправлен на указанную почту.',
                                        'prize_image_alt' => 'Приз',
                                        'spins_info_format' => 'Вращений: {count} / {limit}',
                                        'spins_limit_format' => 'Лимит вращений: {limit}',
                                        'error_init_guest' => 'Ошибка инициализации: не удалось создать гостя',
                                        'error_init' => 'Ошибка инициализации:',
                                        'error_no_prizes' => 'Нет доступных призов',
                                        'error_load_data' => 'Ошибка загрузки данных:',
                                        'error_spin' => 'При розыгрыше произошла ошибка! Обратитесь в поддержку сервиса.',
                                        'error_general' => 'Ошибка:',
                                        'error_send' => 'Ошибка при отправке',
                                        'error_copy_code' => 'Не удалось скопировать код. Пожалуйста, скопируйте вручную:',
                                        'wheel_default_name' => 'Колесо Фортуны',
                                    ];
                                    $component->state($defaultSettings);
                                }
                            })
                            ,
                ]),
                Section::make('Настройки стилей')
                    ->description('')
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed(true)
                    ->schema([
                            Forms\Components\CodeEditor::make('style_settings')
                                ->label('')//__('filament.wheel.style_settings')
                                ->language(Language::Json)
                                ->columnSpanFull()
                                ->default(function () {
                                    return json_encode(\App\Models\Wheel::getDefaultStyleSettings(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                })
                                ->afterStateHydrated(function ($component, $state, $record) {
                                    if ($record === null && (empty($state) || $state === 'null' || $state === null)) {
                                        $component->state(json_encode(\App\Models\Wheel::getDefaultStyleSettings(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                                    } elseif (is_array($state)) {
                                        $component->state(json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                                    } elseif (is_string($state)) {
                                        // Проверяем, валидный ли это JSON
                                        $decoded = json_decode($state, true);
                                        if (json_last_error() !== JSON_ERROR_NONE) {
                                            // Если не валидный JSON, используем дефолтные значения
                                            $component->state(json_encode(\App\Models\Wheel::getDefaultStyleSettings(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                                        }
                                    }
                                })
                                ->dehydrateStateUsing(function ($state) {
                                    if (is_string($state)) {
                                        $decoded = json_decode($state, true);
                                        return $decoded !== null ? $decoded : [];
                                    }
                                    return is_array($state) ? $state : [];
                                })
                                ,
                ]),
                Forms\Components\CodeEditor::make('widget_embed_code')
                    ->label(__('filament.wheel.widget_embed_code'))
                    ->language(Language::Html)
                    //->disabled()
                    ->dehydrated(false)
                    ->columnSpanFull()
                    ->visible(fn ($record) => $record !== null)
                    ->afterStateHydrated(function ($component, $state, $record) {
                        if (!$record) {
                            return;
                        }

                        $baseUrl = config('app.url');
                        $widgetScriptUrl = $baseUrl . '/js/lucky-wheel-widget.js';
                        $apiUrl = $baseUrl . '/api/widget';
                        $slug = $record->slug ?? 'wheel-slug';

                        $code = <<<HTML
                            <script src="{$widgetScriptUrl}"></script>
                            <script>
                              LuckyWheel.init({
                                slug: '{$slug}',
                                apiUrl: '{$apiUrl}',
                                open: true,
                              });
                            </script>
                            HTML;

/*
                                onSpin: function(spinData) {
                                  console.log('Вращение выполнено:', spinData);
                                },
                                onWin: function(prize) {
                                  console.log('Выигрыш:', prize);
                                  alert('Поздравляем! Вы выиграли: ' + prize.name);
                                },
                                onError: function(error) {
                                  console.error('Ошибка:', error);
                                },
                                onLoad: function() {
                                  console.log('Виджет загружен');
                                }
*/

                        $component->state($code);
                    }),
                Forms\Components\TextInput::make('public_url')
                    ->label(__('filament.wheel.public_url'))
                    ->disabled()
                    ->dehydrated(false)
                    ->columnSpanFull()
                    ->visible(fn ($record) => $record !== null)
                    ->afterStateHydrated(function ($component, $state, $record) {
                        if (!$record) {
                            return;
                        }
                        $url = route('widget.embed.web', $record->slug);
                        $component->state($url);
                    })
                    ->copyable()
                    ->helperText(fn ($record) => $record ? __('filament.wheel.public_url_hint') : null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('')
                    ->disk('public')
                    ->visibility('public')
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('filament.wheel.name'))
                    ->searchable()
                    ->sortable(),
//                Tables\Columns\TextColumn::make('user.name')
//                    ->label(__('filament.wheel.user_id'))
//                    ->searchable()
//                    ->sortable(),
//                Tables\Columns\TextColumn::make('slug')
//                    ->label(__('filament.wheel.slug'))
//                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('filament.wheel.is_active'))
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('spins_limit')
                    ->label(__('filament.wheel.spins_limit'))
                    ->numeric()
                    ->default('∞') // ♾
                    ->sortable(),
                Tables\Columns\TextColumn::make('refresh_hour')
                    ->label(__('filament.wheel.refresh_hour'))
                    ->default('00:00')
                    ->sortable(),
                Tables\Columns\TextColumn::make('probability_type')
                    ->label(__('filament.wheel.probability_type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'random' => __('filament.wheel.probability_type_random'),
                        'weighted' => __('filament.wheel.probability_type_weighted'),
                        default => $state,
                    })
                    ->color(fn (string $state): string => match($state) {
                        'random' => 'info',
                        'weighted' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('prizes_count')
                    ->label(__('filament.wheel.prizes_count'))
                    ->counts('prizes')
                    ->sortable(),
                Tables\Columns\TextColumn::make('spins_count')
                    ->label(__('filament.wheel.spins_count'))
                    ->counts('spins')
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label(__('filament.wheel.public_url'))
                    ->formatStateUsing(fn ($record) => route('widget.embed.web', $record->slug))
                    ->url(fn ($record) => route('widget.embed.web', $record->slug))
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->copyable()
                    ->copyMessage(__('filament.wheel.public_url_copied'))
                    ->limit(30)
                    ->sortable(false),
                Tables\Columns\TextColumn::make('starts_at')
                    ->label(__('filament.wheel.starts_at'))
                    ->dateTime()
                    ->sortable()
                    ->hidden(),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label(__('filament.wheel.ends_at'))
                    ->dateTime()
                    ->sortable()
                    ->hidden(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament.wheel.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('filament.wheel.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('filament.wheel.is_active'))
                    ->placeholder(__('filament.all'))
                    ->trueLabel(__('filament.active'))
                    ->falseLabel(__('filament.inactive'))
                    ->native(false),
            ])
            ->actions([
                Action::make('duplicate')
                    ->label('Копировать')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('info')
                    ->iconButton()
                    ->requiresConfirmation()
                    ->modalHeading('Копировать колесо')
                    ->modalDescription('Создать копию колеса со всеми призами?')
                    ->modalSubmitActionLabel('Копировать')
                    ->action(function (Wheel $record) {
                        $newWheel = null;
                        
                        DB::transaction(function () use ($record, &$newWheel) {
                            // Копируем колесо
                            $newWheel = $record->replicate();
                            $newWheel->name = $record->name . ' - копия';
                            $newWheel->slug = static::generateUniqueSlug($record->slug);
                            $newWheel->is_active = false; // Делаем неактивным по умолчанию
                            $newWheel->user_id = auth()->id();
                            
                            // Удаляем вычисляемые поля перед сохранением
                            $newWheel->offsetUnset('prizes_count');
                            $newWheel->offsetUnset('spins_count');
                            
                            $newWheel->save();

                            // Копируем все призы
                            foreach ($record->prizes as $prize) {
                                $newPrize = $prize->replicate();
                                $newPrize->wheel_id = $newWheel->id;
                                $newPrize->quantity_used = 0; // Сбрасываем счетчик использованных призов
                                $newPrize->save();
                            }
                        });

                        \Filament\Notifications\Notification::make()
                            ->title('Колесо успешно скопировано')
                            ->success()
                            ->send();

                        return redirect(static::getUrl('edit', ['record' => $newWheel]));
                    }),
                EditAction::make()->iconButton(),
                DeleteAction::make()->iconButton(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ;
    }

    public static function getRelations(): array
    {
        return [
            WheelResource\RelationManagers\PrizesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWheels::route('/'),
            'create' => Pages\CreateWheel::route('/create'),
            'edit' => Pages\EditWheel::route('/{record}/edit'),
        ];
    }

    /**
     * Генерирует уникальный slug для копии колеса
     */
    private static function generateUniqueSlug(string $originalSlug): string
    {
        $baseSlug = $originalSlug . '-copy';
        $slug = $baseSlug;
        $counter = 1;

        while (Wheel::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}



