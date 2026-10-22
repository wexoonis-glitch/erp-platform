<?php

namespace App\Plugins;

use App\Models\ExtensionSubscription;
use App\Models\Plugin;
use App\Models\TenantModule;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class PluginManager
{
    private const FORBIDDEN_SYMBOLS = [
        'eval(',
        'exec(',
        'system(',
        'shell_exec(',
        'passthru(',
        'proc_open(',
        'popen(',
        'pcntl_exec(',
        'base64_decode(',
        'assert(',
    ];

    /**
     * plugins/ klasörünü tara, tüm manifest'leri döndür.
     * @return array<string, PluginManifest>
     */
    public function discoverFromDisk(): array
    {
        $baseDir = base_path('plugins');
        if (!is_dir($baseDir)) {
            return [];
        }

        $found = [];
        foreach (scandir($baseDir) as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            $pluginDir = "{$baseDir}/{$entry}";
            if (!is_dir($pluginDir)) continue;

            $manifestPath = "{$pluginDir}/manifest.json";
            if (!file_exists($manifestPath)) continue;

            try {
                $manifest = PluginManifest::fromFile($manifestPath);
                $found[$manifest->id] = $manifest;
            } catch (\Throwable $e) {
                // Bozuk manifest'i atla
                continue;
            }
        }

        return $found;
    }

    /**
     * Eklentiyi belirtilen tenant için kur.
     */
    public function install(string $tenantId, PluginManifest $manifest): Plugin
    {
        // 1) Yasak sembol taraması
        $this->assertNoForbiddenSymbols($manifest->path);

        // 2) Zorunlu modüller hedef tenant'ta kurulu mu?
        foreach ($manifest->requires as $moduleId) {
            $has = TenantContext::runAs($tenantId, function () use ($tenantId, $moduleId) {
                return TenantModule::where('tenant_id', $tenantId)
                    ->where('module_id', $moduleId)
                    ->where('status', 'active')
                    ->exists();
            });
            if (!$has) {
                throw ValidationException::withMessages([
                    'requires' => "Zorunlu modül kurulu değil: {$moduleId}",
                ]);
            }
        }

        // 3) Handler sınıfları var mı?
        $this->assertHandlersExist($manifest);

        return DB::transaction(function () use ($tenantId, $manifest) {
            $plugin = Plugin::create([
                'id'           => (string) Str::uuid(),
                'tenant_id'    => $tenantId,
                'plugin_id'    => $manifest->id,
                'name'         => $manifest->name,
                'path'         => $manifest->path,
                'version'      => $manifest->version,
                'manifest'     => $manifest->toArray(),
                'vendor'       => $manifest->vendor,
                'checksum'     => $manifest->checksum,
                'status'       => 'active',
                'installed_at' => now(),
                'enabled_at'   => now(),
            ]);

            // Event abonelikleri
            foreach ($manifest->listeners as $eventKey => $handlerClass) {
                ExtensionSubscription::create([
                    'id'            => (string) Str::uuid(),
                    'tenant_id'     => $tenantId,
                    'plugin_id'     => $manifest->id,
                    'event_key'     => $eventKey,
                    'handler_class' => $handlerClass,
                    'status'        => 'active',
                ]);
            }

            return $plugin;
        });
    }

    public function disable(string $tenantId, string $pluginId): void
    {
        TenantContext::runAs($tenantId, function () use ($tenantId, $pluginId) {
            Plugin::where('tenant_id', $tenantId)
                ->where('plugin_id', $pluginId)
                ->update(['status' => 'disabled']);
            ExtensionSubscription::where('tenant_id', $tenantId)
                ->where('plugin_id', $pluginId)
                ->update(['status' => 'inactive']);
        });
    }

    public function enable(string $tenantId, string $pluginId): void
    {
        TenantContext::runAs($tenantId, function () use ($tenantId, $pluginId) {
            Plugin::where('tenant_id', $tenantId)
                ->where('plugin_id', $pluginId)
                ->update(['status' => 'active', 'enabled_at' => now()]);
            ExtensionSubscription::where('tenant_id', $tenantId)
                ->where('plugin_id', $pluginId)
                ->update(['status' => 'active']);
        });
    }

    public function uninstall(string $tenantId, string $pluginId): void
    {
        TenantContext::runAs($tenantId, function () use ($tenantId, $pluginId) {
            ExtensionSubscription::where('tenant_id', $tenantId)
                ->where('plugin_id', $pluginId)
                ->delete();
            Plugin::where('tenant_id', $tenantId)
                ->where('plugin_id', $pluginId)
                ->delete();
        });
    }

    private function assertNoForbiddenSymbols(string $path): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') continue;
            $content = file_get_contents($file->getPathname());
            foreach (self::FORBIDDEN_SYMBOLS as $symbol) {
                if (stripos($content, $symbol) !== false) {
                    throw ValidationException::withMessages([
                        'security' => "Yasak sembol tespit edildi ({$symbol}) — dosya: " . basename($file->getPathname()),
                    ]);
                }
            }
        }
    }

    private function assertHandlersExist(PluginManifest $manifest): void
    {
        // Autoloader'ı geçici olarak kaydet
        $this->registerAutoloaderFor($manifest);

        foreach ($manifest->listeners as $eventKey => $class) {
            if (!class_exists($class)) {
                throw ValidationException::withMessages([
                    'listeners' => "Listener sınıfı bulunamadı: {$class}",
                ]);
            }
            if (!is_subclass_of($class, \App\Events\EventSubscriber::class)) {
                throw ValidationException::withMessages([
                    'listeners' => "Listener EventSubscriber'ı uygulamalı: {$class}",
                ]);
            }
        }
    }

    public function registerAutoloaderFor(PluginManifest $manifest): void
    {
        if (empty($manifest->namespace) || !is_dir($manifest->path . '/src')) {
            return;
        }

        spl_autoload_register(function ($class) use ($manifest) {
            $ns = $manifest->namespace;
            if (!str_starts_with($class, $ns)) {
                return;
            }
            $relative = str_replace($ns, '', $class);
            $file = $manifest->path . '/src/' . str_replace('\\', '/', $relative) . '.php';
            if (file_exists($file)) {
                require_once $file;
            }
        });
    }
}
