<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Filament\Support\Icons\Heroicon;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static ?string $navigationLabel = 'Пользователи';

    protected static ?string $modelLabel = 'Пользователь';

    protected static ?string $pluralModelLabel = 'Пользователи';

    protected static ?int $navigationSort = 99;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label('Имя')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\Select::make('role')
                    ->label('Роль')
                    ->options(function () {
                        $options = [
                            User::ROLE_OWNER => 'Владелец',
                            User::ROLE_MANAGER => 'Менеджер',
                        ];
                        if (auth()->user()?->isAdmin()) {
                            $options[User::ROLE_ADMIN] = 'Администратор';
                        }
                        return $options;
                    })
                    ->required()
                    ->default(User::ROLE_MANAGER)
                    ->reactive()
                    ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('owner_id', $state === User::ROLE_OWNER ? null : null)),
                Forms\Components\Select::make('owner_id')
                    ->label('Владелец')
                    ->relationship('owner', 'name', function ($query) {
                        $user = auth()->user();
                        $query = $query->where('role', User::ROLE_OWNER);
                        if ($user && $user->isOwner()) {
                            $query->where('company_id', $user->company_id);
                        }
                        return $query;
                    })
                    ->searchable()
                    ->preload()
                    ->visible(fn (Forms\Get $get) => $get('role') === User::ROLE_MANAGER)
                    ->required(fn (Forms\Get $get) => $get('role') === User::ROLE_MANAGER)
                    ->reactive()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        if ($state) {
                            $owner = \App\Models\User::find($state);
                            if ($owner && $owner->company_id) {
                                $set('company_id', $owner->company_id);
                            }
                        }
                    }),
                Forms\Components\TextInput::make('password')
                    ->label('Пароль')
                    ->password()
                    ->dehydrated(fn ($state) => filled($state))
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->required(fn (string $context): bool => $context === 'create')
                    ->minLength(8),
                Forms\Components\Hidden::make('company_id'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Имя')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('role')
                    ->label('Роль')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        User::ROLE_ADMIN => 'Администратор',
                        User::ROLE_OWNER => 'Владелец',
                        User::ROLE_MANAGER => 'Менеджер',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        User::ROLE_ADMIN => 'warning',
                        User::ROLE_OWNER => 'danger',
                        User::ROLE_MANAGER => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Компания')
                    ->searchable()
                    ->sortable()
                    ->visible(fn () => auth()->user()?->isAdmin() ?? false),
                Tables\Columns\TextColumn::make('wheels_count')
                    ->label('Колёс')
                    ->counts('wheels')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Роль')
                    ->options(function () {
                        $options = [
                            User::ROLE_OWNER => 'Владелец',
                            User::ROLE_MANAGER => 'Менеджер',
                        ];
                        if (auth()->user()?->isAdmin()) {
                            $options[User::ROLE_ADMIN] = 'Администратор';
                        }
                        return $options;
                    }),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn ($record) => $record->id !== auth()->id()),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user?->isOwner() || $user?->isAdmin() ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        return $user?->isOwner() || $user?->isAdmin() ?? false;
    }
}

