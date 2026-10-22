<?php

namespace App\Providers;

use App\Events\EventBus;
use App\Events\Listeners\LogTenantCreated;
use App\Extensions\ExtensionDispatcher;
use App\Models\CustomField;
use App\Models\CustomObject;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use App\Observers\AuditObserver;
use App\Plugins\ExtensionRegistry;
use App\Plugins\PluginManager;
use App\Registry\FunctionRegistryService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FunctionRegistryService::class, fn() => new FunctionRegistryService());
        $this->app->singleton(EventBus::class, fn() => new EventBus());
        $this->app->singleton(ExtensionDispatcher::class, fn($app) => new ExtensionDispatcher($app->make(EventBus::class)));
        $this->app->singleton(PluginManager::class, fn() => new PluginManager());
        $this->app->singleton(ExtensionRegistry::class, fn($app) => new ExtensionRegistry($app->make(PluginManager::class)));
    }

    public function boot(): void
    {
        /** @var EventBus $bus */
        $bus = $this->app->make(EventBus::class);
        $bus->registerListener('tenant.created', LogTenantCreated::class);

        Livewire::setUpdateRoute(function ($handle) {
            return Route::post('/livewire/update', $handle)->middleware(['web', 'web.auth']);
        });

        Tenant::observe(AuditObserver::class);
        User::observe(AuditObserver::class);
        CustomField::observe(AuditObserver::class);
        CustomObject::observe(AuditObserver::class);
        TenantModule::observe(AuditObserver::class);

        // Tier 2 — eklenti listener'larını yükle (defansif)
        try {
            $this->app->make(ExtensionRegistry::class)->boot($bus);
        } catch (\Throwable $e) {
            Log::warning('[AppServiceProvider] ExtensionRegistry boot başarısız', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
