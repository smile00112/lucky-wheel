<?php

namespace App\Filament\Schemas;

use App\Models\PlatformIntegration;
use App\Models\Prize;
use App\Services\TelegramConnector;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Components\Section;

class BotWebHookFieldSchema
{
    public static function field(): Section
    {
     //   $record = $record();

        return Section::make('Webhook')
            ->schema([
                Forms\Components\TextInput::make('webhook_url')
                    ->label('URL вебхука')
                    ->disabled()
                    ->dehydrated(false)
                    ->afterStateHydrated(function ($component, $record) {
                        if (!$record) {
                            return;
                        }
                        $baseUrl = config('app.url');
                        $platform = $record->platform;

                        switch ($platform){
                            case 'telegram':
                                $url = $baseUrl . '/telegram/' . $record->id . '/webhook';
                                break;
                            case 'vk':
                                $url = $baseUrl . '/vk/' . $record->id . '/webhook';
                                break;
                            case 'max':
                                $url = $baseUrl . '/max/' . $record->id . '/webhook';
                                break;
                            default:
                                $url = $baseUrl . '/default/' . $record->id . '/webhook';
                                break;
                        }
                        $component->state($url);
                    })
                    ->helperText('URL для регистрации вебхука'),
                Actions::make([
                    Action::make('register_webhook')
                        ->label('Зарегистрировать вебхук')
                        ->icon('heroicon-o-link')
                        ->color('success')
                        ->action(function ($record) {
                            if (!$record || !$record->bot_token) {
                                throw new \Exception('Токен бота не указан');
                            }

                            $baseUrl = config('app.url');
                            $webhookUrl = $baseUrl . '/telegram/' . $record->id . '/webhook';

                            $connector = new TelegramConnector();
                            $result = $connector->registerWebhook($record, $webhookUrl);

                            if ($result) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Вебхук успешно зарегистрирован')
                                    ->success()
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title('Не удалось зарегистрировать вебхук')
                                    ->danger()
                                    ->send();
                                //throw new \Exception('Не удалось зарегистрировать вебхук');
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Регистрация вебхука')
                        ->modalDescription('Вы уверены, что хотите зарегистрировать вебхук для этого бота?')
                        ->visible(fn ($record) => $record && $record->platform === PlatformIntegration::PLATFORM_TELEGRAM),
                ])
                    ->visible(fn ($record) => $record && $record->platform === PlatformIntegration::PLATFORM_TELEGRAM),
            ])
            ->collapsible()
            //->visible(fn ($record) => $record && $record->platform === PlatformIntegration::PLATFORM_TELEGRAM)
            ;

    }

}

