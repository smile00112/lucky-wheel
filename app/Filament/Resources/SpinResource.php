<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SpinResource\Pages;
use App\Models\Spin;
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

class SpinResource extends Resource
{
    protected static ?string $model = Spin::class;

    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static ?string $navigationLabel = 'Вращения';

    protected static ?string $modelLabel = 'Вращение';

    protected static ?string $pluralModelLabel = 'Вращения';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('wheel_id')
                    ->label(__('filament.spin.wheel_id'))
                    ->relationship('wheel', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('guest_id')
                    ->label(__('filament.spin.guest_id'))
                    ->relationship('guest', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => ($record->name ?: $record->email ?: $record->phone) . ' (ID: ' . $record->id . ')')
                    ->searchable(['name', 'email', 'phone'])
                    ->required()
                    ->preload(),
                Forms\Components\Select::make('prize_id')
                    ->label(__('filament.spin.prize_id'))
                    ->relationship('prize', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),
                Forms\Components\Select::make('status')
                    ->label(__('filament.spin.status'))
                    ->options([
                        'pending' => __('filament.spin.status_pending'),
                        'completed' => __('filament.spin.status_completed'),
                        'claimed' => __('filament.spin.status_claimed'),
                        'expired' => __('filament.spin.status_expired'),
                    ])
                    ->default('completed')
                    ->required(),
                Forms\Components\TextInput::make('ip_address')
                    ->label(__('filament.spin.ip_address'))
                    ->maxLength(45),
                Forms\Components\Textarea::make('user_agent')
                    ->label(__('filament.spin.user_agent'))
                    ->rows(2)
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('claimed_at')
                    ->label(__('filament.spin.claimed_at'))
                    ->native(false),
                Forms\Components\KeyValue::make('metadata')
                    ->label(__('filament.spin.metadata'))
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('wheel.name')
                    ->label(__('filament.spin.wheel_id'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('guest.name')
                    ->label(__('filament.spin.guest_id'))
                    ->searchable(['guests.name', 'guests.email', 'guests.phone'])
                    ->sortable()
                    ->formatStateUsing(fn ($record) => $record->guest
                        ? ($record->guest->name ?: $record->guest->email ?: $record->guest->phone ?: __('filament.spin.guest_number') . $record->guest->id)
                        : '-'),
                Tables\Columns\TextColumn::make('guest.email')
                    ->label(__('filament.spin.guest_name'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('prize.name')
                    ->label(__('filament.spin.prize_id'))
                    ->searchable()
                    ->sortable()
                    ->default(__('filament.spin.no_prize'))
                    ->color(fn ($record) => $record->prize_id ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('filament.spin.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'pending' => __('filament.spin.status_pending'),
                        'completed' => __('filament.spin.status_completed'),
                        'claimed' => __('filament.spin.status_claimed'),
                        'expired' => __('filament.spin.status_expired'),
                        default => $state,
                    })
                    ->color(fn (string $state): string => match($state) {
                        'pending' => 'warning',
                        'completed' => 'info',
                        'claimed' => 'success',
                        'expired' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label(__('filament.spin.ip_address'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('claimed_at')
                    ->label(__('filament.spin.claimed_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament.spin.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('wheel_id')
                    ->label(__('filament.spin.wheel_id'))
                    ->relationship('wheel', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('filament.spin.status'))
                    ->options([
                        'pending' => __('filament.spin.status_pending'),
                        'completed' => __('filament.spin.status_completed'),
                        'claimed' => __('filament.spin.status_claimed'),
                        'expired' => __('filament.spin.status_expired'),
                    ]),
                Tables\Filters\Filter::make('has_prize')
                    ->label(__('filament.spin.has_prize'))
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('prize_id'))
                    ->toggle(),
                Tables\Filters\Filter::make('no_prize')
                    ->label(__('filament.spin.no_prize'))
                    ->query(fn (Builder $query): Builder => $query->whereNull('prize_id'))
                    ->toggle(),
            ])
            ->actions([
                EditAction::make(),
                Action::make('mark_claimed')
                    ->label(__('filament.spin.mark_claimed'))
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status !== 'claimed' && $record->prize_id !== null)
                    ->action(fn ($record) => $record->markAsClaimed()),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSpins::route('/'),
            'create' => Pages\CreateSpin::route('/create'),
            'edit' => Pages\EditSpin::route('/{record}/edit'),
        ];
    }
}

