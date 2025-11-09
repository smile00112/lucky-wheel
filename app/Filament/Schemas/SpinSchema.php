<?php

namespace App\Filament\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class SpinSchema
{
    /**
     * Получить схему формы для вращения
     *
     * @param Schema $schema
     * @param bool $includeWheelId Включить ли поле wheel_id
     * @param bool $includeGuestId Включить ли поле guest_id
     * @param bool $includePrizeId Включить ли поле prize_id
     * @return Schema
     */
    public static function form(
        Schema $schema,
        bool $includeWheelId = true,
        bool $includeGuestId = true,
        bool $includePrizeId = true
    ): Schema {
        $components = [];

        if ($includeWheelId) {
            $components[] = Forms\Components\Select::make('wheel_id')
                ->label(__('filament.spin.wheel_id'))
                ->relationship('wheel', 'name')
                ->required()
                ->searchable()
                ->preload();
        }

        if ($includeGuestId) {
            $components[] = Forms\Components\Select::make('guest_id')
                ->label(__('filament.spin.guest_id'))
                ->relationship('guest', 'name')
                ->getOptionLabelFromRecordUsing(fn ($record) => ($record->name ?: $record->email ?: $record->phone) . ' (ID: ' . $record->id . ')')
                ->searchable(['name', 'email', 'phone'])
                ->required()
                ->preload();
        }

        if ($includePrizeId) {
            $components[] = Forms\Components\Select::make('prize_id')
                ->label(__('filament.spin.prize_id'))
                ->relationship('prize', 'name')
                ->searchable()
                ->preload()
                ->nullable();
        }

        $components = array_merge($components, [
            Forms\Components\TextInput::make('code')
                ->label(__('filament.spin.code'))
                ->maxLength(6)
                ->disabled() // Код генерируется автоматически, не редактируется
                ->helperText(__('filament.spin.code_hint')),
            Forms\Components\Toggle::make('email_notification')
                ->label(__('filament.spin.email_notification'))
                ->helperText(__('filament.spin.email_notification_hint'))
                ->default(false),
            Forms\Components\Select::make('status')
                ->label(__('filament.spin.status'))
                ->options([
                    'pending' => __('filament.spin.status_pending'),
                    //'completed' => __('filament.spin.status_completed'),
                    'claimed' => __('filament.spin.status_claimed'),
                    //'expired' => __('filament.spin.status_expired'),
                ])
                ->default('completed')

            ,
            Forms\Components\TextInput::make('ip_address')
                ->label(__('filament.spin.ip_address'))
                ->maxLength(45),
            Forms\Components\Textarea::make('user_agent')
                ->label(__('filament.spin.user_agent'))
                ->rows(2)
                ->columnSpanFull(),
            Forms\Components\DateTimePicker::make('created_at')
                ->label(__('filament.spin.claimed_at'))
                ->native(false)
                ->hidden()
            ,
            Forms\Components\KeyValue::make('metadata')
                ->label(__('filament.spin.metadata'))
                ->columnSpanFull(),
        ]);

        return $schema->components($components);
    }

    /**
     * Получить схему таблицы для вращения
     *
     * @param Table $table
     * @param bool $includeWheelColumn Включить ли колонку wheel
     * @param bool $includeGuestColumn Включить ли колонку guest
     * @param bool $includeFullColumns Включить ли все колонки (ip_address, claimed_at, created_at)
     * @param array $additionalColumns Дополнительные колонки для добавления
     * @return Table
     */
    public static function table(
        Table $table,
        bool $includeWheelColumn = true,
        bool $includeGuestColumn = true,
        bool $includeFullColumns = true,
        array $additionalColumns = []
    ): Table {
        $columns = [];

        if ($includeWheelColumn) {
            $columns[] = Tables\Columns\TextColumn::make('wheel.name')
                ->label(__('filament.spin.wheel_id'))
                ->searchable()
                ->sortable();
        }

        if ($includeGuestColumn) {
            $columns[] = Tables\Columns\TextColumn::make('guest.name')
                ->label(__('filament.spin.guest_id'))
                ->searchable(['guests.name', 'guests.email', 'guests.phone'])
                ->sortable()
                ->formatStateUsing(fn ($record) => $record->guest
                    ? ($record->guest->name ?: $record->guest->email ?: $record->guest->phone ?: __('filament.spin.guest_number') . $record->guest->id)
                    : '-');
            $columns[] = Tables\Columns\TextColumn::make('guest.email')
                ->label(__('filament.spin.guest_name'))
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true);
        }

        $columns[] = Tables\Columns\TextColumn::make('prize.name')
            ->label(__('filament.spin.prize_id'))
            ->searchable()
            ->sortable()
            ->default(__('filament.spin.no_prize'))
            ->color(fn ($record) => $record->prize_id ? 'success' : 'gray');

        $columns[] = Tables\Columns\TextColumn::make('code')
            ->label(__('filament.spin.code'))
            ->searchable()
            ->sortable()
            ->default('-')
            ->copyable()
            ->copyMessage(__('filament.spin.code_copied'))
            ->copyMessageDuration(1500);

        $columns[] = Tables\Columns\IconColumn::make('email_notification')
            ->label(__('filament.spin.email_notification'))
            ->boolean()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);

        $columns[] = Tables\Columns\TextColumn::make('status')
            ->label(__('filament.spin.status'))
            ->badge()
            ->formatStateUsing(fn (string $state): string => match($state) {
                'pending' => __('filament.spin.status_pending'),
                //'completed' => __('filament.spin.status_completed'),
                'claimed' => __('filament.spin.status_claimed'),
                //'expired' => __('filament.spin.status_expired'),
                default => $state,
            })
            ->color(fn (string $state): string => match($state) {
                'pending' => 'warning',
                //'completed' => 'info',
                'claimed' => 'success',
                //'expired' => 'danger',
                default => 'gray',
            })
            ->hidden();

        if ($includeFullColumns) {
            $columns = array_merge($columns, [
                Tables\Columns\TextColumn::make('ip_address')
                    ->label(__('filament.spin.ip_address'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('claimed_at')
                    ->label(__('filament.spin.claimed_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->hidden(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament.spin.created_at'))
                    ->dateTime()
                    ->sortable(),
            ]);
        }

        // Добавляем дополнительные колонки, если они переданы
        if (!empty($additionalColumns)) {
            $columns = array_merge($columns, $additionalColumns);
        }

        $filters = [];

        if ($includeWheelColumn) {
            $filters[] = Tables\Filters\SelectFilter::make('wheel_id')
                ->label(__('filament.spin.wheel_id'))
                ->relationship('wheel', 'name')
                ->searchable()
                ->preload();
        }

        $filters = array_merge($filters, [
//            Tables\Filters\SelectFilter::make('status')
//                ->label(__('filament.spin.status'))
//                ->options([
//                    'pending' => __('filament.spin.status_pending'),
//                    'completed' => __('filament.spin.status_completed'),
//                    'claimed' => __('filament.spin.status_claimed'),
//                    'expired' => __('filament.spin.status_expired'),
//                ])
//            ,
//            Tables\Filters\Filter::make('has_prize')
//                ->label(__('filament.spin.has_prize'))
//                ->query(fn (Builder $query): Builder => $query->whereNotNull('prize_id'))
//                ->toggle(),
//            Tables\Filters\Filter::make('no_prize')
//                ->label(__('filament.spin.no_prize'))
//                ->query(fn (Builder $query): Builder => $query->whereNull('prize_id'))
//                ->toggle(),
        ]);

        $actions = [
            \Filament\Actions\EditAction::make(),
            Action::make('mark_claimed')
                ->label(__('filament.spin.mark_claimed'))
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn ($record) => $record->status !== 'claimed' && $record->prize_id !== null)
                ->action(fn ($record) => $record->markAsClaimed()),
            Action::make('send_email')
                ->label(__('filament.spin.send_email'))
                ->icon(Heroicon::OutlinedEnvelope)
                ->color('info')
                ->requiresConfirmation()
                ->visible(fn ($record) => $record->isWin() && $record->guest->email)
                ->action(function ($record) {
                    if ($record->sendWinEmail()) {
                        \Filament\Notifications\Notification::make()
                            ->title(__('filament.spin.email_sent'))
                            ->success()
                            ->send();
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title(__('filament.spin.email_send_failed'))
                            ->danger()
                            ->send();
                    }
                }),
            \Filament\Actions\DeleteAction::make(),
        ];

        return $table
            ->columns($columns)
            ->filters($filters)
            ->actions($actions)
            ->defaultSort('created_at', 'desc');
    }
}

