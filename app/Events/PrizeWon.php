<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Spin;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PrizeWon
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Spin $spin
    ) {
    }
}

