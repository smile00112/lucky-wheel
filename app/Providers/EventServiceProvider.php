<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\PrizeWon;
use App\Listeners\SendTelegramPrizeNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        PrizeWon::class => [
            SendTelegramPrizeNotification::class,
        ],
    ];

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }

    public function boot(): void
    {
        parent::boot();

    }
}

