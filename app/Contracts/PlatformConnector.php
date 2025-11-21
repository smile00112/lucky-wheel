<?php

namespace App\Contracts;

use App\Models\PlatformIntegration;
use App\Models\Spin;

interface PlatformConnector
{
    public function registerWebhook(PlatformIntegration $integration, string $url): bool;

    public function sendSpinResult(PlatformIntegration $integration, Spin $spin, string $userId): bool;

    public function buildLaunchUrl(PlatformIntegration $integration, string $wheelSlug, array $params = []): string;

    public function validateAuthData(array $data): ?array;
}

