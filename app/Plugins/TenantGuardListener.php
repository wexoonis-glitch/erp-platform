<?php

namespace App\Plugins;

use App\Events\EventSubscriber;
use App\Models\Plugin;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Log;

/**
 * Bir eklenti listener'ını sarmalar; yalnızca geçerli tenant'ta
 * eklenti aktifse gerçek handler'ı çağırır.
 */
final class TenantGuardListener implements EventSubscriber
{
    public function __construct(
        private readonly string $pluginId,
        private readonly string $handlerClass,
    ) {}

    public function handle(string $eventKey, array $payload): void
    {
        $tenantId = TenantContext::id();
        if ($tenantId === null) return;

        $enabled = Plugin::where('tenant_id', $tenantId)
            ->where('plugin_id', $this->pluginId)
            ->where('status', 'active')
            ->exists();

        if (!$enabled) return;

        if (!class_exists($this->handlerClass)) {
            Log::warning("[ExtensionRegistry] Handler sınıfı yok: {$this->handlerClass}", [
                'plugin_id' => $this->pluginId,
                'event_key' => $eventKey,
            ]);
            return;
        }

        $handler = new $this->handlerClass();
        if ($handler instanceof EventSubscriber) {
            $handler->handle($eventKey, $payload);
        }
    }
}
