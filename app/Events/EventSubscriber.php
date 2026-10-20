<?php

namespace App\Events;

interface EventSubscriber
{
    /**
     * @param string $eventKey  Ör: 'tenant.created', 'ap.invoice.matched'
     * @param array  $payload   Event ile taşınan veri
     */
    public function handle(string $eventKey, array $payload): void;
}
