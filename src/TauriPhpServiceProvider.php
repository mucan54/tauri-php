<?php

namespace Mucan54\TauriPhp;

use Illuminate\Support\ServiceProvider;
use Mucan54\TauriPhp\Console\BuildCommand;
use Mucan54\TauriPhp\Console\CleanCommand;
use Mucan54\TauriPhp\Console\DevCommand;
use Mucan54\TauriPhp\Console\InitCommand;
use Mucan54\TauriPhp\Console\MobileDevCommand;
use Mucan54\TauriPhp\Console\MobileInitCommand;
use Mucan54\TauriPhp\Console\PackageCommand;
use Mucan54\TauriPhp\Http\Middleware\DetectTauriEnvironment;
use Mucan54\TauriPhp\Plugins\Camera\Camera;
use Mucan54\TauriPhp\Plugins\Geolocation\Geolocation;
use Mucan54\TauriPhp\Plugins\Notification\Notification;
use Mucan54\TauriPhp\Plugins\Storage\Storage;
use Mucan54\TauriPhp\Plugins\Vibration\Vibration;
use Mucan54\TauriPhp\Services\TauriPhpService;

class TauriPhpServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge package configuration
        $this->mergeConfigFrom(
            __DIR__.'/../config/tauri-php.php',
            'tauri-php'
        );

        // Register the main service as a singleton
        $this->app->singleton('tauri-php', function ($app) {
            return new TauriPhpService;
        });

        // Register mobile plugins as singletons
        $this->app->singleton(Camera::class, function () {
            return new Camera;
        });

        $this->app->singleton(Notification::class, function () {
            return new Notification;
        });

        $this->app->singleton(Vibration::class, function () {
            return new Vibration;
        });

        $this->app->singleton(Geolocation::class, function () {
            return new Geolocation;
        });

        $this->app->singleton(Storage::class, function () {
            return new Storage;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Load package routes
        $this->loadRoutesFrom(__DIR__.'/../routes/tauri.php');

        // Register middleware
        $router = $this->app['router'];
        $router->aliasMiddleware('tauri', DetectTauriEnvironment::class);

        if ($this->app->runningInConsole()) {
            // Publish configuration file
            $this->publishes([
                __DIR__.'/../config/tauri-php.php' => config_path('tauri-php.php'),
            ], 'tauri-php-config');

            // Publish stub files
            $this->publishes([
                __DIR__.'/../stubs' => base_path('stubs/tauri-php'),
            ], 'tauri-php-stubs');

            // Publish routes file
            $this->publishes([
                __DIR__.'/../routes/tauri.php' => base_path('routes/tauri.php'),
            ], 'tauri-php-routes');

            // Register console commands
            $this->commands([
                InitCommand::class,
                BuildCommand::class,
                DevCommand::class,
                PackageCommand::class,
                CleanCommand::class,
                MobileInitCommand::class,
                MobileDevCommand::class,
            ]);
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return ['tauri-php'];
    }
}
