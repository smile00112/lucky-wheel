<?php

namespace Database\Seeders;

use App\Models\PlatformIntegration;
use Illuminate\Database\Seeder;

class PlatformIntegrationSeeder extends Seeder
{
    public function run(): void
    {
        PlatformIntegration::firstOrCreate(
            ['platform' => PlatformIntegration::PLATFORM_TELEGRAM],
            [
                'bot_token' => null,
                'bot_username' => null,
                'webhook_url' => null,
                'is_active' => false,
                'settings' => [],
            ]
        );
    }
}
