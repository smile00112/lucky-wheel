<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WheelResource\Pages;
use App\Models\Wheel;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Components\CodeEditor\Enums\Language;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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
                Forms\Components\KeyValue::make('style_settings')
                    ->label(__('filament.wheel.style_settings'))
                    ->columnSpanFull()
                    ->afterStateHydrated(function ($component, $state, $record) {
                        if (!$record && (empty($state) || !is_array($state))) {
                            $defaultStyleSettings = [
                                'content' => [
                                    'font_family' => 'Arial, sans-serif',
                                    'background' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                                ],
                                'container' => [
                                    'background' => '#ffffff',
                                    'border_radius' => '20px',
                                    'padding' => '30px 20px',
                                    'max_width' => '450px',
                                ],
                                'title' => [
                                    'color' => '#333333',
                                    'font_size' => '1.8em',
                                    'margin_bottom' => '20px',
                                ],
                                'description' => [
                                    'color' => '#666666',
                                    'font_size' => '14px',
                                    'margin_bottom' => '35px',
                                ],
                                'pointer' => [
                                    'color' => '#ff4444',
                                ],
                                'spin_button' => [
                                    'background' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                                    'color' => '#ffffff',
                                    'font_size' => '16px',
                                    'font_weight' => 'bold',
                                    'padding' => '15px 40px',
                                    'border_radius' => '50px',
                                    'max_width' => '300px',
                                ],
                                'won_prize_block' => [
                                    'background' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                                    'color' => '#ffffff',
                                    'padding' => '10px 20px',
                                    'border_radius' => '10px',
                                ],
                                'won_prize_label' => [
                                    'font_size' => '11px',
                                    'opacity' => '0.9',
                                ],
                                'won_prize_name' => [
                                    'font_size' => '14px',
                                    'font_weight' => 'bold',
                                ],
                                'win_notification' => [
                                    'background' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                                    'color' => '#ffffff',
                                    'padding' => '20px',
                                    'border_radius' => '15px 15px 0 0',
                                ],
                                'win_notification_title' => [
                                    'font_size' => '1.3em',
                                ],
                                'win_notification_message' => [
                                    'font_size' => '14px',
                                ],
                                'win_notification_code_input' => [
                                    'background' => 'rgba(255, 255, 255, 0.9)',
                                    'color' => '#333333',
                                    'font_size' => '16px',
                                    'font_weight' => 'bold',
                                    'border_radius' => '6px',
                                    'padding' => '12px',
                                ],
                                'win_notification_submit_button' => [
                                    'background' => '#ffffff',
                                    'color' => '#667eea',
                                    'font_size' => '16px',
                                    'font_weight' => 'bold',
                                    'border_radius' => '8px',
                                    'padding' => '14px',
                                ],
                                'spins_info' => [
                                    'font_size' => '12px',
                                    'color' => '#999999',
                                ],
                                'error' => [
                                    'background' => '#ffeeee',
                                    'border_color' => '#ffcccc',
                                    'color' => '#cc3333',
                                    'padding' => '15px',
                                    'border_radius' => '10px',
                                ],
                            ];
                            $component->state($defaultStyleSettings);
                        }
                    })
                    ,
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
}



