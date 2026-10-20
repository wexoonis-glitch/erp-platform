<?php

namespace App\Events\Listeners;

use App\Events\EventSubscriber;
use Illuminate\Support\Facades\Log;

final class LogTenantCreated implements EventSubscriber
{
    public function handle(string $eventKey, array $payload): void
    {
        Log::info('[EventBus] tenant.created alındı', [
            'event_key' => $eventKey,
            'payload'   => $payload,
        ]);
    }
}
