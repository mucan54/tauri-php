<?php

namespace Mucan54\TauriPhp\Tests\Feature;

use Mucan54\TauriPhp\Tests\TestCase;

class ServiceProviderTest extends TestCase
{
    /** @test */
    public function it_registers_the_service_provider()
    {
        $this->assertTrue($this->app->providerIsLoaded(\Mucan54\TauriPhp\TauriPhpServiceProvider::class));
    }

    /** @test */
    public function it_registers_the_facade()
    {
        $this->assertTrue(class_exists('Mucan54\TauriPhp\Facades\TauriPhp'));
    }

    /** @test */
    public function it_loads_configuration()
    {
        $config = config('tauri-php');

        $this->assertIsArray($config);
        $this->assertArrayHasKey('platforms', $config);
        $this->assertArrayHasKey('frankenphp', $config);
        $this->assertArrayHasKey('mobile', $config);
    }

    /** @test */
    public function it_registers_console_commands()
    {
        $commands = [
            'tauri:init',
            'tauri:build',
            'tauri:dev',
            'tauri:package',
            'tauri:clean',
            'tauri:mobile-init',
            'tauri:mobile-dev',
        ];

        foreach ($commands as $command) {
            $this->assertTrue(
                \Illuminate\Support\Arr::has(
                    $this->app[\Illuminate\Contracts\Console\Kernel::class]->all(),
                    $command
                ),
                "Command {$command} is not registered"
            );
        }
    }
}
