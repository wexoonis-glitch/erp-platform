<?php

namespace HelloAnalytics;

use App\Events\EventSubscriber;
use Illuminate\Support\Facades\Log;

class HelloAnalyticsListener implements EventSubscriber
{
    public function handle(string $eventKey, array $payload): void
    {
        Log::info('[HelloAnalytics] user.created alındı', [
            'event'   => $eventKey,
            'user_id' => $payload['id'] ?? null,
            'email'   => $payload['email'] ?? null,
        ]);
    }
}
