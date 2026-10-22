<?php

namespace App\Plugins;

use App\Events\EventBus;
use App\Models\ExtensionSubscription;
use App\Models\Plugin;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

final class ExtensionRegistry
{
    public function __construct(private readonly PluginManager $manager) {}

    public function boot(EventBus $bus): void
    {
        // Migration henüz çalışmamışsa hiçbir şey yapma
        try {
            if (!Schema::hasTable('plugin')) {
                return;
            }
            if (!Schema::hasColumn('plugin', 'path')) {
                return;
            }
            if (!Schema::hasTable('extension_subscription')) {
                return;
            }
        } catch (\Throwable $e) {
            // DB erişimi bile yoksa sessizce çık
            return;
        }

        // 1) Autoloader — her plugin path'i için
        try {
            $paths = Plugin::query()
                ->whereNotNull('path')
                ->distinct()
                ->pluck('path')
                ->all();

            foreach ($paths as $path) {
                $manifestPath = "{$path}/manifest.json";
                if (!file_exists($manifestPath)) continue;

                try {
                    $manifest = PluginManifest::fromFile($manifestPath);
                    $this->manager->registerAutoloaderFor($manifest);
                } catch (\Throwable $e) {
                    Log::warning("[ExtensionRegistry] Manifest okunamadı: {$manifestPath}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[ExtensionRegistry] Autoloader aşaması başarısız', [
                'error' => $e->getMessage(),
            ]);
        }

        // 2) Listener kaydı — (plugin_id + event_key) bazında tekil
        try {
            $subscriptions = ExtensionSubscription::query()
                ->where('status', 'active')
                ->get(['plugin_id', 'event_key', 'handler_class']);

            $seen = [];
            foreach ($subscriptions as $sub) {
                $key = $sub->plugin_id . ':' . $sub->event_key;
                if (isset($seen[$key])) continue;
                $seen[$key] = true;

                $bus->registerListener(
                    $sub->event_key,
                    new TenantGuardListener($sub->plugin_id, $sub->handler_class)
                );
            }
        } catch (\Throwable $e) {
            Log::warning('[ExtensionRegistry] Listener kaydı başarısız', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
