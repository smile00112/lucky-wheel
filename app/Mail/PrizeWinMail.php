<?php

namespace App\Mail;

use App\Models\Spin;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PrizeWinMail extends Mailable
{
    use Queueable, SerializesModels;

    public Spin $spin;
    public Setting $settings;

    /**
     * Create a new message instance.
     */
    public function __construct(Spin $spin)
    {
        // Загружаем связи для использования в шаблоне
        $this->spin = $spin->load(['prize', 'guest']);
        $this->settings = Setting::getInstance();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        // Перезагружаем настройки, чтобы получить актуальные данные
        $settings = Setting::getInstance();
        $companyName = $settings->company_name ?: 'Колесо фортуны';
        $fromAddress = config('mail.from.address', 'hello@example.com');

        return new Envelope(
            from: new Address($fromAddress, $companyName),
            subject: "Поздравляем! Вы выиграли приз - {$companyName}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.prize-win',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
