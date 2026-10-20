<?php

namespace App\Events;

use App\Models\EventOutbox;
use App\Support\TenantContext;
use LogicException;

final class EventBus
{
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';

    /** @var array<string, list<callable|string>> */
    private array $listeners = [];

    public function registerListener(string $eventKey, callable|string $handler): void
    {
        $this->listeners[$eventKey][] = $handler;
    }

    /**
     * Event'i outbox'a yazar.
     * - Tenant bağlamı yoksa: geçici olarak $tenantId'ye bağlanır, insert sonrası temizlenir.
     * - Tenant bağlamı zaten $tenantId ise: doğrudan insert.
     * - Tenant bağlamı farklıysa ve superadmin değilse: hata (güvenlik).
     */
    public function publish(string $eventKey, array $payload, ?string $tenantId = null): int
    {
        $tenantId ??= TenantContext::id();

        if ($tenantId === null) {
            throw new LogicException(
                "EventBus::publish: tenant bağlamı yok ve açıkça tenant_id verilmedi."
            );
        }

        $current = TenantContext::id();
        $isSuper = TenantContext::isSuperadmin();

        if ($current !== null && $current !== $tenantId && !$isSuper) {
            throw new LogicException(
                "EventBus::publish: aktif tenant bağlamı ({$current}) ile hedef tenant ({$tenantId}) uyuşmuyor."
            );
        }

        if ($current === $tenantId) {
            return $this->insertEvent($eventKey, $payload, $tenantId);
        }

        return TenantContext::runAs($tenantId, function () use ($eventKey, $payload, $tenantId) {
            return $this->insertEvent($eventKey, $payload, $tenantId);
        });
    }

    /**
     * Bekleyen event'leri işler.
     * - Sorgu superadmin bağlamında çalışır (tüm tenant'ların pending'lerini görür).
     * - Her event kendi tenant bağlamında dispatch edilir ve güncellenir.
     */
    public function dispatchPending(int $limit = 100): int
    {
        // 1. Superadmin bağlamında tüm pending'leri al (RLS bypass)
        $rows = TenantContext::runAs(
            self::NULL_TENANT,
            fn() => EventOutbox::where('status', 'pending')
                ->where('available_at', '<=', now())
                ->orderBy('id')
                ->limit($limit)
                ->get(),
            superadmin: true
        );

        $processed = 0;

        // 2. Her event'i kendi tenant bağlamında işle
        foreach ($rows as $row) {
            try {
                TenantContext::runAs($row->tenant_id, function () use ($row) {
                    $this->dispatch($row);
                    $row->update([
                        'status'       => 'processed',
                        'processed_at' => now(),
                    ]);
                });
                $processed++;
            } catch (\Throwable $e) {
                $newAttempts = $row->attempts + 1;

                TenantContext::runAs($row->tenant_id, function () use ($row, $newAttempts, $e) {
                    $row->update([
                        'attempts'     => $newAttempts,
                        'status'       => $newAttempts >= 5 ? 'failed' : 'pending',
                        'last_error'   => mb_substr($e->getMessage(), 0, 2000),
                        'available_at' => now()->addSeconds(min(60 * $newAttempts, 3600)),
                    ]);
                });
            }
        }

        return $processed;
    }

    private function insertEvent(string $eventKey, array $payload, string $tenantId): int
    {
        $row = EventOutbox::create([
            'tenant_id'    => $tenantId,
            'event_key'    => $eventKey,
            'payload'      => $payload,
            'status'       => 'pending',
            'attempts'     => 0,
            'available_at' => now(),
        ]);

        return (int) $row->id;
    }

    private function dispatch(EventOutbox $row): void
    {
        $handlers = $this->listeners[$row->event_key] ?? [];

        foreach ($handlers as $handler) {
            if (is_string($handler) && class_exists($handler)) {
                $handler = new $handler();
            }

            if ($handler instanceof EventSubscriber) {
                $handler->handle($row->event_key, $row->payload);
            } elseif (is_callable($handler)) {
                $handler($row->event_key, $row->payload);
            } else {
                throw new \RuntimeException(
                    "EventBus: geçersiz handler tipi ({$row->event_key})"
                );
            }
        }
    }
}
