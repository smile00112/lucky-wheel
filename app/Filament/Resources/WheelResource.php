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
                    ->copyable()
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
                    ->hidden(),
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
                    ->sortable(),
                Tables\Columns\TextColumn::make('refresh_hour')
                    ->label(__('filament.wheel.refresh_hour'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('prizes_count')
                    ->label(__('filament.wheel.prizes_count'))
                    ->counts('prizes')
                    ->sortable(),
                Tables\Columns\TextColumn::make('spins_count')
                    ->label(__('filament.wheel.spins_count'))
                    ->counts('spins')
                    ->sortable(),
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
            ]);
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



