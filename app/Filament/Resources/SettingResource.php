<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingResource\Pages;
use App\Models\Setting;
use App\Support\DefaultTemplates;
use Filament\Forms;
use Filament\Forms\Components\CodeEditor\Enums\Language;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Set;
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
                Forms\Components\TextInput::make('notification_email')
                    ->label('Email для оповещений об отключении колеса')
                    ->email()
                    ->helperText('На этот email будут отправляться уведомления при автоматическом отключении колеса из-за выхода из лимитов')
                    ->maxLength(255)
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
                            ->columnSpanFull()
//                            ->extraActions([
//                                FormAction::make('fill_default_email_template')
//                                    ->label('Вставить шаблон по умолчанию')
//                                    ->action(fn (Set $set) => $set('email_template', DefaultTemplates::email())),
//                            ]),
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
                            ->columnSpanFull()
//                            ->extraActions([
//                                FormAction::make('fill_default_pdf_template')
//                                    ->label('Вставить шаблон по умолчанию')
//                                    ->action(fn (Set $set) => $set('pdf_template', DefaultTemplates::pdf())),
//                            ]),
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

