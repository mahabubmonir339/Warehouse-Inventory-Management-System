<?php

namespace App\Providers;

use App\Services\Rfid\RfidScanProcessor;
use App\Services\Rfid\RfidActionResolver;
use App\Services\Rfid\RfidSyncService;
use Illuminate\Support\ServiceProvider;

/**
 * RFID Module Service Provider
 * Registers RFID services into the Laravel container
 */
class RfidServiceProvider extends ServiceProvider
{
    /**
     * Register RFID services.
     */
    public function register(): void
    {
        // Register RFID services as singletons
        $this->app->singleton(RfidScanProcessor::class, function ($app) {
            return new RfidScanProcessor();
        });

        $this->app->singleton(RfidActionResolver::class, function ($app) {
            return new RfidActionResolver();
        });

        $this->app->singleton(RfidSyncService::class, function ($app) {
            return new RfidSyncService();
        });
    }

    /**
     * Bootstrap RFID services.
     */
    public function boot(): void
    {
        // You can add additional bootstrapping here
        // E.g., event listeners, observers, etc.
    }
}
