<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PlatformIntegration;
use Illuminate\Database\Eloquent\Collection;

class TelegramMessageService
{
    private TelegramTextService $textService;

    public function __construct(TelegramTextService $textService)
    {
        $this->textService = $textService;
    }

    public function getWelcomeMessage(?PlatformIntegration $integration = null): string
    {
        return $this->textService->get($integration, 'welcome');
    }

    public function getContactSavedMessage(?PlatformIntegration $integration = null): string
    {
        return $this->textService->get($integration, 'contact_saved');
    }

    public function getContactSavedButWheelNotConfigured(?PlatformIntegration $integration = null): string
    {
        return $this->textService->get($integration, 'contact_saved_wheel_not_configured');
    }

    public function getContactError(?PlatformIntegration $integration = null): string
    {
        return $this->textService->get($integration, 'contact_error');
    }

    public function getContactNotOwned(?PlatformIntegration $integration = null): string
    {
        return $this->textService->get($integration, 'contact_not_owned');
    }

    public function getContactProcessingError(?PlatformIntegration $integration = null): string
    {
        return $this->textService->get($integration, 'contact_processing_error');
    }

    public function getWheelNotConfigured(?PlatformIntegration $integration = null): string
    {
        return $this->textService->get($integration, 'wheel_not_configured');
    }

    public function getPhoneRequired(?PlatformIntegration $integration = null): string
    {
        return $this->textService->get($integration, 'phone_required');
    }

    public function getUserNotFound(?PlatformIntegration $integration = null): string
    {
        return $this->textService->get($integration, 'user_not_found');
    }

    public function getUserNotDetermined(?PlatformIntegration $integration = null): string
    {
        return $this->textService->get($integration, 'user_not_determined');
    }

    public function getHistoryEmpty(?PlatformIntegration $integration = null): string
    {
        return $this->textService->get($integration, 'history_empty');
    }

    public function getHistoryMessage(Collection $wins, ?PlatformIntegration $integration = null): string
    {
        $title = $this->textService->get($integration, 'history_title');
        $messageText = $title . "\n\n";

        $connector = app(TelegramConnector::class);

        foreach ($wins as $win) {
            $date = $win->created_at->format('d.m.Y H:i');
            $prize = $win->prize;
            $wheel = $win->wheel ?? null;

            $dateText = $this->textService->get($integration, 'history_item_date');
            if ($dateText) {
                $messageText .= $connector->replaceVariables($dateText, $wheel, $prize, $win) . "\n";
            } else {
                $messageText .= __('telegram.history_item_date', ['date' => $date], 'ru') . "\n";
            }

            $prizeText = $this->textService->get($integration, 'history_item_prize');
            if ($prizeText) {
                $messageText .= $connector->replaceVariables($prizeText, $wheel, $prize, $win) . "\n";
            } else {
                $prizeName = $prize ? $prize->getNameWithoutSeparator() : 'Неизвестный приз';
                $messageText .= __('telegram.history_item_prize', ['prize' => $prizeName], 'ru') . "\n";
            }

            $codeText = $this->textService->get($integration, 'history_item_code');
            if ($codeText) {
                $messageText .= $connector->replaceVariables($codeText, $wheel, $prize, $win) . "\n";
            }

            $qrImageText = $this->textService->get($integration, 'history_item_qr_image');
            if ($qrImageText) {
                $messageText .= $connector->replaceVariables($qrImageText, $wheel, $prize, $win) . "\n";
            }

            $messageText .= "\n";
        }

        return $messageText;
    }

    public function getUseStartCommand(?PlatformIntegration $integration = null): string
    {
        return $this->textService->get($integration, 'use_start_command');
    }

    public function getRequestContactMessage(?PlatformIntegration $integration = null): string
    {
        return $this->textService->get($integration, 'request_contact');
    }

    public function getSpinWelcomeMessage(?PlatformIntegration $integration = null): string
    {
        return $this->textService->get($integration, 'spin_welcome');
    }

    public function getSpinButtonMessage(?PlatformIntegration $integration = null): string
    {
        return $this->textService->get($integration, 'spin_button');
    }
}
