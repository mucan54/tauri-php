<?php

namespace Mucan54\TauriPhp\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Mucan54\TauriPhp\TauriPhpServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            TauriPhpServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default configuration
        config()->set('tauri-php', require __DIR__.'/../config/tauri-php.php');
    }
}
