<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PrizeWon;
use App\Mail\PrizeWinMail;
use App\Services\MailConfigService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPrizeWinEmail
{
    public function handle(PrizeWon $event): void
    {
        $spin = $event->spin;

        if (!$spin->isWin()) {
            return;
        }

        $guest = $spin->guest;
        if (!$guest || !$guest->email) {
            return;
        }

        // Защита от дублирования
        if ($spin->email_notification) {
            Log::warning('Email notification already sent for spin', ['spin_id' => $spin->id]);
            return;
        }

        try {
            $spin->load('wheel.user');
            $user = $spin->wheel->user;
            
            $mailConfigService = app(MailConfigService::class);
            $mailConfigService->configureForUser($user);
            
            Mail::to($guest->email)->send(new PrizeWinMail($spin));
            
            $spin->update(['email_notification' => true]);
            
            Log::info('Prize win email sent successfully', [
                'spin_id' => $spin->id,
                'guest_email' => $guest->email,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send prize win email', [
                'error' => $e->getMessage(),
                'spin_id' => $spin->id,
                'guest_email' => $guest->email,
            ]);
        }
    }
}


