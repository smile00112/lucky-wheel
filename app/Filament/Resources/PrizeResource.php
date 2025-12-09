<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PrizeResource\Pages;
use App\Filament\Schemas\PrizeSchema;
use App\Models\Prize;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

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
        $headerActions = [
            Action::make('filter_today')
                ->label('Сегодня')
                ->icon(Heroicon::OutlinedCalendar)
                ->color('gray')
                ->outlined()
                ->action(function ($livewire) {
                    $livewire->tableFilters = array_merge($livewire->tableFilters ?? [], [
                        'spins_date' => [
                            'spins_created_from' => Carbon::today()->format('Y-m-d'),
                            'spins_created_until' => Carbon::today()->format('Y-m-d'),
                        ],
                    ]);
                }),

            Action::make('filter_yesterday')
                ->label('Вчера')
                ->icon(Heroicon::OutlinedCalendar)
                ->color('gray')
                ->outlined()
                ->action(function ($livewire) {
                    $livewire->tableFilters = array_merge($livewire->tableFilters ?? [], [
                        'spins_date' => [
                            'spins_created_from' => Carbon::yesterday()->format('Y-m-d'),
                            'spins_created_until' => Carbon::yesterday()->format('Y-m-d'),
                        ],
                    ]);
                }),

            Action::make('filter_week')
                ->label('За неделю')
                ->icon(Heroicon::OutlinedCalendar)
                ->color('gray')
                ->outlined()
                ->action(function ($livewire) {
                    $livewire->tableFilters = array_merge($livewire->tableFilters ?? [], [
                        'spins_date' => [
                            'spins_created_from' => Carbon::now()->subWeek()->startOfDay()->format('Y-m-d'),
                            'spins_created_until' => Carbon::now()->format('Y-m-d'),
                        ],
                    ]);
                }),

            Action::make('filter_month')
                ->label('За месяц')
                ->icon(Heroicon::OutlinedCalendar)
                ->color('gray')
                ->outlined()
                ->action(function ($livewire) {
                    $livewire->tableFilters = array_merge($livewire->tableFilters ?? [], [
                        'spins_date' => [
                            'spins_created_from' => Carbon::now()->subMonth()->startOfDay()->format('Y-m-d'),
                            'spins_created_until' => Carbon::now()->format('Y-m-d'),
                        ],
                    ]);
                }),
        ];

        $actions = [
            Action::make('reset_quantity_used')
                ->label('Сбросить счётчик')
                ->requiresConfirmation()
                ->color('warning')
                ->icon('heroicon-o-arrow-path')
                ->action(function (Prize $record) {
                    $record->update(['quantity_used' => 0]);
                }),
            \Filament\Actions\EditAction::make()->iconButton(),
            \Filament\Actions\DeleteAction::make()->iconButton(),
        ];

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
            ->headerActions($headerActions)
//            ->actions([
//                EditAction::make()->iconButton(),
//                DeleteAction::make()->iconButton(),
//            ])
                ->recordActions($actions)
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

