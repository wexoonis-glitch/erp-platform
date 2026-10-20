<?php

namespace App\Providers;

use App\Events\EventBus;
use App\Events\Listeners\LogTenantCreated;
use App\Extensions\ExtensionDispatcher;
use App\Registry\FunctionRegistryService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            FunctionRegistryService::class,
            fn() => new FunctionRegistryService()
        );

        $this->app->singleton(
            EventBus::class,
            fn() => new EventBus()
        );

        $this->app->singleton(
            ExtensionDispatcher::class,
            fn($app) => new ExtensionDispatcher($app->make(EventBus::class))
        );
    }

    public function boot(): void
    {
        /** @var EventBus $bus */
        $bus = $this->app->make(EventBus::class);

        // tenant.created → log'a yaz
        $bus->registerListener('tenant.created', LogTenantCreated::class);
    }
}
