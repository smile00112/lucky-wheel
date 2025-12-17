<?php

namespace App\Mail;

use App\Models\Setting;
use App\Models\Wheel;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class WheelDisabledMail extends Mailable
{
    use Queueable, SerializesModels;

    public Wheel $wheel;
    public Setting $settings;

    public function __construct(Wheel $wheel)
    {
        $this->wheel = $wheel;
        $this->settings = Setting::getInstance();
    }

    public function envelope(): Envelope
    {
        $companyName = $this->settings->company_name ?: 'Колесо фортуны';
        $fromAddress = config('mail.from.address', 'hello@example.com');

        return new Envelope(
            from: new Address($fromAddress, $companyName),
            subject: "Колесо '{$this->wheel->name}' было автоматически отключено - {$companyName}",
        );
    }

    public function content(): Content
    {
        $logoHtml = '';
        if ($this->settings->logo) {
            $logoUrl = $this->getFileUrl($this->settings->logo);
            $logoAlt = $this->settings->company_name ?: 'Логотип';
            $logoHtml = "<img src=\"{$logoUrl}\" alt=\"{$logoAlt}\" style=\"max-width: 200px; margin-bottom: 20px;\">";
        }

        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .content { background: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
                .info-item { margin: 10px 0; }
                .info-label { font-weight: bold; color: #555; }
                .footer { text-align: center; color: #888; font-size: 12px; margin-top: 30px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    {$logoHtml}
                    <h1>Уведомление об отключении колеса</h1>
                </div>
                <div class='content'>
                    <p>Колесо призов было автоматически отключено из-за выхода из лимитов.</p>
                    <div class='info-item'>
                        <span class='info-label'>Название колеса:</span> {$this->wheel->name}
                    </div>
                    <div class='info-item'>
                        <span class='info-label'>Slug:</span> {$this->wheel->slug}
                    </div>
                    <div class='info-item'>
                        <span class='info-label'>Дата отключения:</span> " . now()->format('d.m.Y H:i') . "
                    </div>
                    <div class='info-item'>
                        <span class='info-label'>Причина:</span> Все призы были разыграны (достигнут лимит)
                    </div>
                </div>
                <div class='footer'>
                    <p>Это автоматическое уведомление от системы {$this->settings->company_name ?: 'Колесо фортуны'}</p>
                </div>
            </div>
        </body>
        </html>";

        return new Content(
            htmlString: $html,
        );
    }

    protected function getFileUrl(string $path): string
    {
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        if (str_starts_with($path, '/')) {
            return url($path);
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        return asset('storage/' . ltrim($path, '/'));
    }
}

