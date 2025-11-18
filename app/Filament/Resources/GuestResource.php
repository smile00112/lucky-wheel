<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GuestResource\Pages;
use App\Models\Guest;
use App\Models\Wheel;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class GuestResource extends Resource
{
    protected static ?string $model = Guest::class;

    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedUser;

    protected static ?string $navigationLabel = 'Гости';

    protected static ?string $modelLabel = 'Гость';

    protected static ?string $pluralModelLabel = 'Гости';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label(__('filament.guest.name'))
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->label(__('filament.guest.email'))
                    ->email()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->label(__('filament.guest.phone'))
                    ->tel()
                    ->maxLength(255),
                Forms\Components\TextInput::make('ip_address')
                    ->label(__('filament.guest.ip_address'))
                    ->maxLength(45),
                Forms\Components\Textarea::make('user_agent')
                    ->label(__('filament.guest.user_agent'))
                    ->rows(2)
                    ->columnSpanFull(),
                Forms\Components\KeyValue::make('metadata')
                    ->label(__('filament.guest.metadata'))
                    ->columnSpanFull(),
            ]);
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
                        'date_today' => ["isActive" => true],
                        'date_yesterday' =>  ["isActive" => false],
                        'date_week' => ["isActive" => false],
                        'date_month' => ["isActive" => false],
                    ]);
                }),

            Action::make('filter_yesterday')
                ->label('Вчера')
                ->icon(Heroicon::OutlinedCalendar)
                ->color('gray')
                ->outlined()
                ->action(function ($livewire) {
                    $livewire->tableFilters = array_merge($livewire->tableFilters ?? [], [
                        'date_today' => ["isActive" => false],
                        'date_yesterday' => ["isActive" => true],
                        'date_week' => ["isActive" => false],
                        'date_month' => ["isActive" => false],
                    ]);
                }),

            Action::make('filter_week')
                ->label('За неделю')
                ->icon(Heroicon::OutlinedCalendar)
                ->color('gray')
                ->outlined()
                ->action(function ($livewire) {
                    $livewire->tableFilters = array_merge($livewire->tableFilters ?? [], [
                        'date_today' => ["isActive" => false],
                        'date_yesterday' => ["isActive" => false],
                        'date_week' => ["isActive" => true],
                        'date_month' => ["isActive" => false],
                    ]);
                }),

            Action::make('filter_month')
                ->label('За месяц')
                ->icon(Heroicon::OutlinedCalendar)
                ->color('gray')
                ->outlined()
                ->action(function ($livewire) {
                    $livewire->tableFilters = array_merge($livewire->tableFilters ?? [], [
                        'date_today' => ["isActive" => false],
                        'date_yesterday' => ["isActive" => false],
                        'date_week' => ["isActive" => false],
                        'date_month' => ["isActive" => true],
                    ]);
                }),
        ];


        return $table

            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label(__('filament.guest.id'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('filament.guest.name'))
                    ->searchable()
                    ->sortable()
                    ->default('-'),
                Tables\Columns\TextColumn::make('email')
                    ->label(__('filament.guest.email'))
                    ->searchable()
                    ->sortable()
                    ->default('-'),
                Tables\Columns\TextColumn::make('phone')
                    ->label(__('filament.guest.phone'))
                    ->searchable()
                    ->sortable()
                    ->default('-'),
                Tables\Columns\TextColumn::make('spins_count')
                    ->label(__('filament.guest.spins_count'))
                    ->counts('spins')
                    ->sortable(),
//                Tables\Columns\TextColumn::make('wins_count')
//                    ->label(__('filament.guest.wins_count'))
//                    ->counts('wins')
//                    ->sortable()
//                    ->toggleable(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label(__('filament.guest.ip_address'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament.guest.created_at'))
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('filament.guest.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('wheel_id')
                    ->label(__('filament.guest.wheel'))
                    ->options(fn () => Wheel::query()->forUser()->pluck('name', 'id'))
                    ->query(fn ($query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn ($q, $value) => $q->whereHas('spins', fn ($sq) => $sq->where('wheel_id', $value))
                    ))
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('has_email')
                    ->label(__('filament.guest.has_email'))
                    ->query(fn ($query) => $query->whereNotNull('email'))
                    ->toggle(),
                Tables\Filters\Filter::make('has_phone')
                    ->label(__('filament.guest.has_phone'))
                    ->query(fn ($query) => $query->whereNotNull('phone'))
                    ->toggle(),
                Tables\Filters\Filter::make('has_name')
                    ->label(__('filament.guest.has_name'))
                    ->query(fn ($query) => $query->whereNotNull('name'))
                    ->toggle(),

                Tables\Filters\Filter::make('date_today')
                    ->label('Сегодня')
                    ->query(fn (Builder $query): Builder => $query->whereHas('spins', function ($q) {
                        $q->whereDate('created_at', Carbon::today());
                    }))
                    ->toggle()
                    ->default([]),

                Tables\Filters\Filter::make('date_yesterday')
                    ->label('Вчера')
                    ->query(fn (Builder $query): Builder => $query->whereHas('spins', function ($q) {
                        $q->whereDate('created_at', Carbon::yesterday());
                    }))
                    ->toggle()
                    ->default([]),

                Tables\Filters\Filter::make('date_week')
                    ->label('За неделю')
                    ->query(fn (Builder $query): Builder => $query->whereHas('spins', function ($q) {
                        $q->whereDate('created_at', '>=', Carbon::now()->subWeek()->startOfDay());
                    }))
                    ->toggle()
                    ->default([]),

                Tables\Filters\Filter::make('date_month')
                    ->label('За месяц')
                    ->query(fn (Builder $query): Builder => $query->whereHas('spins', function ($q) {
                        $q->whereDate('created_at', '>=', Carbon::now()->subMonth()->startOfDay());
                    }))
                    ->toggle()
                    ->default([]),

                Tables\Filters\Filter::make('last_spin_date')
                    ->label(__('filament.guest.last_spin_date'))
                    ->form([
                        Forms\Components\DatePicker::make('last_spin_from')
                            ->label(__('filament.guest.last_spin_from'))
                            ->displayFormat('d.m.Y')
                            ->native(false),
                        Forms\Components\DatePicker::make('last_spin_until')
                            ->label(__('filament.guest.last_spin_until'))
                            ->displayFormat('d.m.Y')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['last_spin_from'] ?? null,
                            function (Builder $query, $date) {
                                $query->whereIn('id', function ($subQuery) use ($date) {
                                    $subQuery->select('guest_id')
                                        ->from('spins')
                                        ->groupBy('guest_id')
                                        ->havingRaw('MAX(created_at) >= ?', [$date]);
                                });
                            }
                        )->when(
                            $data['last_spin_until'] ?? null,
                            function (Builder $query, $date) {
                                $query->whereIn('id', function ($subQuery) use ($date) {
                                    $subQuery->select('guest_id')
                                        ->from('spins')
                                        ->groupBy('guest_id')
                                        ->havingRaw('MAX(created_at) <= ?', [$date . ' 23:59:59']);
                                });
                            }
                        );
                    }),
            ])
            ->headerActions($headerActions)
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            GuestResource\RelationManagers\WinsRelationManager::class,
            GuestResource\RelationManagers\GuestIpAddressesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGuests::route('/'),
            'create' => Pages\CreateGuest::route('/create'),
            'edit' => Pages\EditGuest::route('/{record}/edit'),
        ];
    }
}

