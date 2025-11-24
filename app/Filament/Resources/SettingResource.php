<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingResource\Pages;
use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Components\CodeEditor\Enums\Language;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Настройки';

    protected static ?string $modelLabel = 'Настройки';

    protected static ?string $pluralModelLabel = 'Настройки';

    protected static ?int $navigationSort = 99;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('company_name')
                    ->label(__('filament.setting.company_name'))
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\FileUpload::make('logo')
                    ->label(__('filament.setting.logo'))
                    ->image()
                    ->disk('public')
                    ->directory('settings')
                    ->visibility('public')
                    ->columnSpanFull(),
                Section::make(__('filament.setting.email_template'))
                    ->description('')
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed(true)
                    ->schema([
                        Forms\Components\CodeEditor::make('email_template')
                            ->label('Код шаблона email')
                            ->helperText(__('filament.setting.email_template_hint'))
                            ->language(Language::Html)
                            ->columnSpanFull(),
                    ])
                    ,
                Section::make(__('filament.setting.pdf_template'))
                    ->description('')
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed(true)
                    ->schema([
                        Forms\Components\CodeEditor::make('pdf_template')
                            ->label('Код шаблона PDF')
                            ->helperText(__('filament.setting.pdf_template_hint'))
                            ->language(Language::Html)
                            ->columnSpanFull(),
                    ])

                    ,

            ]);
    }

    public static function table(Table $table): Table
    {
        // Таблица не нужна, так как у нас только одна запись настроек
        return $table
            ->columns([
                //
            ])
            ->paginated(false);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
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
            'index' => Pages\ListSettings::route('/'),
            'edit' => Pages\ManageSettings::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Запрещаем создание новых записей
    }

    public static function canDeleteAny(): bool
    {
        return false; // Запрещаем удаление
    }
}

