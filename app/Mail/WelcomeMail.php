<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address', 'hello@example.com'), config('mail.from.name', 'LuckyWheel')),
            subject: 'Добро пожаловать в LuckyWheel!',
        );
    }

    public function content(): Content
    {
        $adminUrl = url('/admin');
        
        return new Content(
            view: 'emails.welcome',
            with: [
                'user' => $this->user,
                'adminUrl' => $adminUrl,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

