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
                            ->default(function () {
                                return self::getDefaultEmailTemplate();
                            })
                            ->afterStateHydrated(function ($component, $state, $record) {
                                if (empty($state) || $state === null) {
                                    $component->state(self::getDefaultEmailTemplate());
                                }
                            })
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
                            ->default(function () {
                                return self::getDefaultPdfTemplate();
                            })
                            ->afterStateHydrated(function ($component, $state, $record) {
                                if (empty($state) || $state === null) {
                                    $component->state(self::getDefaultPdfTemplate());
                                }
                            })
                            ->columnSpanFull(),
                    ]),
                Section::make('SMTP настройки')
                    ->description('Настройки почтового сервера')
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed(true)
                    ->schema([
                        Forms\Components\Select::make('mail_mailer')
                            ->label('Mailer')
                            ->options([
                                'smtp' => 'SMTP',
                                'sendmail' => 'Sendmail',
                                'mailgun' => 'Mailgun',
                                'ses' => 'SES',
                                'postmark' => 'Postmark',
                                'resend' => 'Resend',
                                'log' => 'Log',
                                'array' => 'Array',
                            ])
                            ->default('smtp')
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('mail_host')
                            ->label('Host')
                            ->maxLength(255)
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('mail_port')
                            ->label('Port')
                            ->numeric()
                            ->default(587)
                            ->columnSpan(1),
                        Forms\Components\Select::make('mail_encryption')
                            ->label('Encryption')
                            ->options([
                                'tls' => 'TLS',
                                'ssl' => 'SSL',
                                null => 'None',
                            ])
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('mail_username')
                            ->label('Username')
                            ->maxLength(255)
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('mail_password')
                            ->label('Password')
                            ->password()
                            ->maxLength(255)
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('mail_from_address')
                            ->label('From Address')
                            ->email()
                            ->maxLength(255)
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('mail_from_name')
                            ->label('From Name')
                            ->maxLength(255)
                            ->columnSpan(1),
                    ])
                    ->columns(2),

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
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && $user->isOwner();
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        $owner = $user->getOwner();
        return $record->user_id === $owner->id;
    }

    protected static function getDefaultEmailTemplate(): string
    {
        return '<!DOCTYPE html>
        <html lang="ru">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Поздравляем с выигрышем!</title>
          <style type="text/css">.rollover span {
            font-size:0;
            }
            #outlook a {
                padding:0;
            }
            .ci {
                mso-style-priority:100!important;
                text-decoration:none!important;
            }
            a[x-apple-data-detectors] {
                color:inherit!important;
                text-decoration:none!important;
                font-size:inherit!important;
                font-family:inherit!important;
                font-weight:inherit!important;
                line-height:inherit!important;
            }
            .b {
                display:none;
                float:left;
                overflow:hidden;
                width:0;
                max-height:0;
                line-height:0;
                mso-hide:all;
            }
        @media only screen and (max-width:600px) {p, ul li, ol li, a { line-height:150%!important } h1, h2, h3, h1 a, h2 a, h3 a { line-height:120% } h1 { font-size:36px!important; text-align:left } h2 { font-size:26px!important; text-align:left } h3 { font-size:20px!important; text-align:left }      .cp p, .cp ul li, .cp ol li, .cp a { font-size:16px!important }  .cn p, .cn ul li, .cn ol li, .cn a { font-size:12px!important } *[class="gmail-fix"] { display:none!important } .cl, .cl h1, .cl h2, .cl h3 { text-align:center!important }       .cc table, .cd table, .ce table, .cc, .ce, .cd { width:100%!important; max-width:600px!important }  .adapt-img { width:100%!important; height:auto!important }  .bz { padding-right:0!important } .by { padding-left:0!important }          table.bq, .esd-block-html table { width:auto!important } table.bp { display:inline-block!important } table.bp td { display:inline-block!important }                                         .a { border-radius:20px } }
        @media screen and (max-width:384px) {.mail-message-content { width:414px!important } }

        </style>
        </head>
        <body style="width:100%;font-family:arial,  helvetica, sans-serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;padding:0;Margin:0">
          <div dir="ltr" class="es-wrapper-color" lang="ru" style="background-color:#FAFAFA"><!--[if gte mso 9]>
                    <v:background xmlns:v="urn:schemas-microsoft-com:vml" fill="t">
                        <v:fill type="tile" color="#fafafa"></v:fill>
                    </v:background>
                <![endif]-->
           <table class="es-wrapper" width="100%" cellspacing="0" cellpadding="0" role="none" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;padding:0;Margin:0;width:100%;height:100%;background-repeat:repeat;background-position:center top;background-color:#FAFAFA">
             <tr>
              <td valign="top" style="padding:0;Margin:0">
               <table cellpadding="0" cellspacing="0" class="cc" align="center" role="none" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;table-layout:fixed !important;width:100%">
                 <tr>
                  <td align="center" style="padding:0;Margin:0">
                   <table bgcolor="#ffffff" class="cp" align="center" cellpadding="0" cellspacing="0" role="none" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;background-color:#FFFFFF;width:600px">
                     <tr>
                      <td align="left" style="padding:0;Margin:0;padding-bottom:10px">
                       <table cellpadding="0" cellspacing="0" width="100%" role="none" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px">
                         <tr>
                          <td align="center" valign="top" style="padding:0;Margin:0;width:600px">
                           <table cellpadding="0" cellspacing="0" width="100%" role="presentation" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px">
                             <tr>
                              <td align="center" class="es-m40b" style="padding:0;Margin:0;padding-top:40px;padding-bottom:40px;margin:0px 0px 20px;background-color:#3EB2E6;font-size:0px"><img src="https://tjojmy.stripocdn.email/content/guids/CABINET_0d83193f8169a8abfbcc850268d61091d0d6792c594b2164c2c233dd7f3fef98/images/badenetkulscaled_1.png" alt="" style="display:block;border:0;outline:none;text-decoration:none;-ms-interpolation-mode:bicubic" width="199" height="76"></td>
                             </tr>
                             <tr>
                              <td align="center" style="padding:0;Margin:0;font-size:0px"><img class="adapt-img" src="https://tjojmy.stripocdn.email/content/guids/CABINET_0d83193f8169a8abfbcc850268d61091d0d6792c594b2164c2c233dd7f3fef98/images/1512b8ec31e84db1a4708324b29c68a7_preobrazovannyj_1.jpg" alt="" style="display:block;border:0;outline:none;text-decoration:none;-ms-interpolation-mode:bicubic" width="200" height="200"></td>
                             </tr>
                             <tr>
                              <td align="center" class="cl" style="padding:0;Margin:0;padding-top:10px;padding-bottom:10px"><h1 style="Margin:0;line-height:38.4px;mso-line-height-rule:exactly;font-family:arial,  helvetica, sans-serif;font-size:24px;font-style:normal;font-weight:bold;color:#154a88"><strong>Ваш тёплый приз от Baden Family<br><span style="font-size:32px;line-height:38.4px">{prize_name}</span></strong></h1></td>
                             </tr>
                             <tr>
                              <td align="center" class="bz by" style="Margin:0;padding-top:5px;padding-bottom:5px;padding-left:40px;padding-right:40px"><p style="Margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial,  helvetica, sans-serif;line-height:36px;color:#333333;font-size:24px"><strong>Дорогой гость!</strong></p></td>
                             </tr>
                             <tr>
                              <td align="center" class="bz by" style="Margin:0;padding-top:5px;padding-bottom:5px;padding-left:40px;padding-right:40px"><p style="Margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial,  helvetica, sans-serif;line-height:21px;color:#333333;font-size:14px">С искренней радостью и особой гордостью сообщаем:<br>вам улыбнулась удача в нашем колесе фортуны!</p></td>
                             </tr>
                             <tr>
                              <td align="center" class="bz by" style="Margin:0;padding-top:5px;padding-bottom:5px;padding-left:40px;padding-right:40px"><p style="Margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial,  helvetica, sans-serif;line-height:21px;color:#333333;font-size:14px">{prize_email_text_after_congratulation_html}</p></td>
                             </tr>
                           </table></td>
                         </tr>
                       </table></td>
                     </tr>
                     <tr>
                      <td align="left" style="padding:0;Margin:0">
                       <table cellpadding="0" cellspacing="0" width="100%" role="none" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px">
                         <tr>
                          <td align="center" valign="top" style="padding:0;Margin:0;width:600px">
                           <table cellpadding="0" cellspacing="0" width="100%" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:separate;border-spacing:0px;border-radius:5px" role="presentation">
                             <tr>
                              <td align="center" class="cl" style="Margin:0;padding-bottom:10px;padding-top:20px;padding-left:20px;padding-right:20px"><h2 style="Margin:0;line-height:38.4px;mso-line-height-rule:exactly;font-family:arial,  helvetica, sans-serif;font-size:32px;font-style:normal;font-weight:bold;color:#154a88"><strong>Промокод на получение</strong></h2></td>
                             </tr>
                             <tr>
                              <td align="center" class="cl a" bgcolor="#3EB2E6" style="Margin:0;padding-top:10px;padding-bottom:20px;padding-left:20px;padding-right:20px"><h1 style="Margin:0;line-height:55.2px;mso-line-height-rule:exactly;font-family:arial,  helvetica, sans-serif;font-size:46px;font-style:normal;font-weight:bold;color:#ffffff"><strong>{prize_value}</strong></h1></td>
                             </tr>
                             <tr>
                              <td align="center" class="bz by" style="Margin:0;padding-top:5px;padding-bottom:5px;padding-left:40px;padding-right:40px"><p style="Margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial,  helvetica, sans-serif;line-height:21px;color:#333333;font-size:14px">Идентификационный код: {code}</p></td>
                             </tr>
                             <tr>
                              <td align="center" class="bz by" style="Margin:0;padding-top:30px;padding-bottom:30px;padding-left:40px;padding-right:40px"><p style="Margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial,  helvetica, sans-serif;line-height:21px;color:#333333;font-size:14px">{prize_email_coupon_after_code_text_html}</p></td>
                             </tr>
                             <tr>
                              <td align="center" style="padding:0;Margin:0;font-size:0">
                               <table cellpadding="0" cellspacing="0" class="bq bp" dir="ltr" role="presentation" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px">
                                 <tr>
                                  <td align="center" valign="top" style="padding:0;Margin:0;padding-right:10px"><a target="_blank" href="https://t.me" style="-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;text-decoration:underline;color:#5C68E2;font-size:14px"><img src="https://tjojmy.stripocdn.email/content/assets/img/messenger-icons/logo-black/telegram-logo-black.png" alt="Telegram" title="Telegram" width="32" height="32" style="display:block;border:0;outline:none;text-decoration:none;-ms-interpolation-mode:bicubic"></a></td>
                                  <td align="center" valign="top" style="padding:0;Margin:0;padding-right:10px"><img src="https://tjojmy.stripocdn.email/content/assets/img/messenger-icons/logo-black/whatsapp-logo-black.png" alt="Whatsapp" title="Whatsapp" width="32" height="32" style="display:block;border:0;outline:none;text-decoration:none;-ms-interpolation-mode:bicubic"></td>
                                  <td align="center" valign="top" style="padding:0;Margin:0;padding-right:10px"><a target="_blank" href="https://vk.com" style="-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;text-decoration:underline;color:#5C68E2;font-size:14px"><img src="https://tjojmy.stripocdn.email/content/guids/CABINET_0d83193f8169a8abfbcc850268d61091d0d6792c594b2164c2c233dd7f3fef98/images/vk.png" alt="" title="Custom" width="32" height="32" style="display:block;border:0;outline:none;text-decoration:none;-ms-interpolation-mode:bicubic"></a></td>
                                  <td align="center" valign="top" style="padding:0;Margin:0;padding-right:10px"><a target="_blank" href="tel:+79999998888" style="-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;text-decoration:underline;color:#5C68E2;font-size:14px"><img src="https://tjojmy.stripocdn.email/content/guids/CABINET_0d83193f8169a8abfbcc850268d61091d0d6792c594b2164c2c233dd7f3fef98/images/phone.png" alt="" title="Custom" width="32" height="32" style="display:block;border:0;outline:none;text-decoration:none;-ms-interpolation-mode:bicubic"></a></td>
                                  <td align="center" valign="top" style="padding:0;Margin:0"><a target="_blank" href="https://baden74.ru" style="-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;text-decoration:underline;color:#5C68E2;font-size:14px"><img src="https://tjojmy.stripocdn.email/content/assets/img/other-icons/logo-black/link-logo-black.png" alt="Website" title="Website" width="32" height="32" style="display:block;border:0;outline:none;text-decoration:none;-ms-interpolation-mode:bicubic"></a></td>
                                 </tr>
                               </table></td>
                             </tr>
                           </table></td>
                         </tr>
                       </table></td>
                     </tr>
                   </table></td>
                 </tr>
               </table>
               <table cellpadding="0" cellspacing="0" class="cc" align="center" role="none" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;table-layout:fixed !important;width:100%">
                 <tr>
                  <td class="es-info-area" align="center" style="padding:0;Margin:0">
                   <table class="cp" align="center" cellpadding="0" cellspacing="0" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;background-color:transparent;width:600px" bgcolor="#00000000" role="none">
                     <tr>
                      <td align="left" style="padding:20px;Margin:0">
                       <table cellpadding="0" cellspacing="0" width="100%" role="none" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px">
                         <tr>
                          <td align="center" valign="top" style="padding:0;Margin:0;width:560px">
                           <table cellpadding="0" cellspacing="0" width="100%" role="presentation" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px">
                             <tr>
                              <td align="center" class="cn" style="padding:0;Margin:0;line-height:14.4px;font-size:12px;color:#CCCCCC"><p style="Margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, helvetica, sans-serif;line-height:14.4px;color:#CCCCCC;font-size:12px"><a target="_blank" href="" style="-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;text-decoration:underline;color:#CCCCCC;font-size:12px"></a>Не хотите получать больше эти письма? <a href="" target="_blank" style="-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;text-decoration:underline;color:#CCCCCC;font-size:12px">Отписаться</a>.<a target="_blank" href="" style="-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;text-decoration:underline;color:#CCCCCC;font-size:12px"></a></p></td>
                             </tr>
                           </table></td>
                         </tr>
                       </table></td>
                     </tr>
                   </table></td>
                 </tr>
               </table></td>
             </tr>
           </table>
          </div>
         </body>
        </html>
        ';
    }

    protected static function getDefaultPdfTemplate(): string
    {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .content {
            margin: 20px 0;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title ?? "Заголовок документа" }}</h1>
    </div>
    <div class="content">
        {{ $content ?? "Содержимое документа" }}
    </div>
    <div class="footer">
        <p>© {{ date("Y") }} {{ $company_name ?? "Компания" }}</p>
    </div>
</body>
</html>';
    }
}

