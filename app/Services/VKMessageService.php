<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PlatformIntegration;
use Illuminate\Database\Eloquent\Collection;

class VKMessageService
{
    private VKTextService $textService;

    public function __construct(VKTextService $textService)
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

        foreach ($wins as $win) {
            $date = $win->created_at->format('d.m.Y H:i');
            $prizeName = $win->prize ? $win->prize->getNameWithoutSeparator() : 'Неизвестный приз';
            $messageText .= "Дата: {$date}\n";
            $messageText .= "Приз: {$prizeName}\n\n";
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

