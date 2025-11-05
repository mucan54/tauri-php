<?php

namespace Mucan54\TauriPhp;

use Illuminate\Support\ServiceProvider;
use Mucan54\TauriPhp\Console\InitCommand;
use Mucan54\TauriPhp\Console\BuildCommand;
use Mucan54\TauriPhp\Console\DevCommand;
use Mucan54\TauriPhp\Console\PackageCommand;
use Mucan54\TauriPhp\Console\CleanCommand;
use Mucan54\TauriPhp\Console\MobileInitCommand;
use Mucan54\TauriPhp\Console\MobileDevCommand;
use Mucan54\TauriPhp\Services\TauriPhpService;

class TauriPhpServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
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
            return new TauriPhpService();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish configuration file
            $this->publishes([
                __DIR__.'/../config/tauri-php.php' => config_path('tauri-php.php'),
            ], 'tauri-php-config');

            // Publish stub files
            $this->publishes([
                __DIR__.'/../stubs' => base_path('stubs/tauri-php'),
            ], 'tauri-php-stubs');

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
     *
     * @return array
     */
    public function provides(): array
    {
        return ['tauri-php'];
    }
}
