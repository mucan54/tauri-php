<?php

namespace Mucan54\TauriPhp\Tests\Unit;

use Mucan54\TauriPhp\Services\TauriPhpService;
use Mucan54\TauriPhp\Tests\TestCase;

class TauriPhpServiceTest extends TestCase
{
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TauriPhpService;
    }

    /** @test */
    public function it_returns_package_version()
    {
        $version = $this->service->getVersion();

        $this->assertIsString($version);
        $this->assertEquals('1.0.0', $version);
    }

    /** @test */
    public function it_checks_if_tauri_is_initialized()
    {
        $initialized = $this->service->isTauriInitialized();

        $this->assertIsBool($initialized);
    }

    /** @test */
    public function it_returns_build_info()
    {
        $buildInfo = $this->service->getBuildInfo();

        $this->assertIsArray($buildInfo);
        $this->assertArrayHasKey('initialized', $buildInfo);
        $this->assertArrayHasKey('version', $buildInfo);
    }
}
