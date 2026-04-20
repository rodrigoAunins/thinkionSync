<?php

namespace App\Providers;

use App\Services\Thinkion\ApiClient;
use App\Services\Thinkion\Reports\ReportRegistry;
use App\Services\Thinkion\Sync\SyncOrchestrator;
use App\Services\Thinkion\Sync\SyncService;
use App\Repositories\Raw\RawReportRepository;
use App\Repositories\Raw\SyncRunRepository;
use Illuminate\Support\ServiceProvider;

class ThinkionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind singletons for the Thinkion integration layer
        $this->app->singleton(ApiClient::class, fn() => new ApiClient());
        $this->app->singleton(ReportRegistry::class, fn() => new ReportRegistry());
        $this->app->singleton(RawReportRepository::class, fn() => new RawReportRepository());
        $this->app->singleton(SyncRunRepository::class, fn() => new SyncRunRepository());

        $this->app->singleton(SyncService::class, fn($app) => new SyncService(
            $app->make(ApiClient::class),
            $app->make(SyncRunRepository::class),
            $app->make(RawReportRepository::class),
        ));

        $this->app->singleton(SyncOrchestrator::class, fn($app) => new SyncOrchestrator(
            $app->make(ReportRegistry::class),
            $app->make(SyncService::class),
        ));
    }

    public function boot(): void
    {
        // Merge thinkion config
        $this->mergeConfigFrom(
            config_path('thinkion.php'),
            'thinkion'
        );
    }
}
