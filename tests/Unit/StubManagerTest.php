<?php

namespace Mucan54\TauriPhp\Tests\Unit;

use Mucan54\TauriPhp\Services\StubManager;
use Mucan54\TauriPhp\Tests\TestCase;

class StubManagerTest extends TestCase
{
    protected $stubManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stubManager = new StubManager;
    }

    /** @test */
    public function it_can_get_stubs_path()
    {
        $path = $this->stubManager->getStubsPath();

        $this->assertIsString($path);
        $this->assertStringContainsString('stubs', $path);
    }

    /** @test */
    public function it_can_check_if_stub_exists()
    {
        $exists = $this->stubManager->exists('package.json');

        $this->assertTrue($exists);
    }

    /** @test */
    public function it_returns_false_for_non_existent_stub()
    {
        $exists = $this->stubManager->exists('non-existent-file.txt');

        $this->assertFalse($exists);
    }

    /** @test */
    public function it_can_get_stub_content()
    {
        $content = $this->stubManager->get('package.json');

        $this->assertIsString($content);
        $this->assertStringContainsString('tauri', $content);
    }

    /** @test */
    public function it_can_replace_variables_in_stub()
    {
        $content = $this->stubManager->get('env/tauri.env', [
            'APP_NAME' => 'Test App',
            'APP_IDENTIFIER' => 'com.test.app',
        ]);

        $this->assertStringContainsString('Test App', $content);
        $this->assertStringContainsString('com.test.app', $content);
        $this->assertStringNotContainsString('{{APP_NAME}}', $content);
    }
}
