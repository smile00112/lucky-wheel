<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Mail\MailManager;
use Illuminate\Support\Facades\Config;

class MailConfigService
{
    /**
     * Настроить mailer для пользователя на основе его настроек
     * Если настройки в БД не заполнены, используются значения из .env
     */
    public function configureForUser(User $user): void
    {
        $owner = $user->getOwner();
        $settings = Setting::getForUser($owner);
        $smtpConfig = $settings->getSmtpConfig();

        // Настройка default mailer только если указан в БД
        if (!empty($settings->mail_mailer)) {
            Config::set('mail.default', $smtpConfig['mailer']);
        }

        // Настройка SMTP mailer только если хотя бы одно поле заполнено
        $hasSmtpSettings = !empty($settings->mail_host) || 
                          !empty($settings->mail_port) || 
                          !empty($settings->mail_username) || 
                          !empty($settings->mail_password);

        if ($hasSmtpSettings) {
            $smtpMailerConfig = [
                'transport' => 'smtp',
                'host' => $smtpConfig['host'],
                'port' => $smtpConfig['port'],
                'username' => $smtpConfig['username'],
                'password' => $smtpConfig['password'],
                'timeout' => null,
                'local_domain' => parse_url((string) config('app.url', 'http://localhost'), PHP_URL_HOST),
            ];
            
            if ($smtpConfig['encryption']) {
                $smtpMailerConfig['encryption'] = $smtpConfig['encryption'];
            }
            
            Config::set('mail.mailers.smtp', $smtpMailerConfig);
        }

        // Настройка From адреса только если заполнено в БД
        if (!empty($settings->mail_from_address) || !empty($settings->mail_from_name)) {
            Config::set('mail.from', $smtpConfig['from']);
        }
    }

    /**
     * Сбросить конфигурацию mailer к значениям из .env
     */
    public function resetToEnvConfig(): void
    {
        // Сбрасываем к исходным значениям из .env через env()
        Config::set('mail.default', env('MAIL_MAILER', 'log'));
        
        Config::set('mail.mailers.smtp', [
            'transport' => 'smtp',
            'scheme' => env('MAIL_SCHEME'),
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 2525),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
            'encryption' => env('MAIL_ENCRYPTION'),
        ]);
        
        Config::set('mail.from', [
            'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
            'name' => env('MAIL_FROM_NAME', 'Example'),
        ]);
    }

    /**
     * Получить настроенный MailManager для пользователя
     */
    public function getMailerForUser(User $user): MailManager
    {
        $this->configureForUser($user);
        return app(MailManager::class);
    }
}

