<?php

namespace App\Extensions;

use App\Events\EventBus;

final class ExtensionDispatcher
{
    /** @var array<string, list<callable>> */
    private array $beforeHooks = [];

    /** @var array<string, list<callable>> */
    private array $afterHooks = [];

    public function __construct(private readonly EventBus $bus) {}

    public function registerBefore(string $functionId, callable $hook): void
    {
        $this->beforeHooks[$functionId][] = $hook;
    }

    public function registerAfter(string $functionId, callable $hook): void
    {
        $this->afterHooks[$functionId][] = $hook;
    }

    public function before(string $functionId, array $payload): array
    {
        foreach ($this->beforeHooks[$functionId] ?? [] as $hook) {
            $result = $hook($payload);
            if (is_array($result)) {
                $payload = $result;
            }
        }

        return $payload;
    }

    public function after(string $functionId, array $payload, array $result): void
    {
        foreach ($this->afterHooks[$functionId] ?? [] as $hook) {
            $hook($payload, $result);
        }
    }

    public function publish(string $eventKey, array $payload, ?string $tenantId = null): int
    {
        return $this->bus->publish($eventKey, $payload, $tenantId);
    }
}
