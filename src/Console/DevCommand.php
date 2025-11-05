<?php

namespace Mucan54\TauriPhp\Console;

use Illuminate\Console\Command;
use Mucan54\TauriPhp\Exceptions\TauriPhpException;
use Mucan54\TauriPhp\Services\EnvTauriManager;
use Mucan54\TauriPhp\Services\TauriConfigGenerator;
use Mucan54\TauriPhp\Traits\RunsProcesses;
use Symfony\Component\Process\Process;

class DevCommand extends Command
{
    use RunsProcesses;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tauri:dev
                            {--host=127.0.0.1 : Development server host}
                            {--port=8080 : Development server port}
                            {--verbose : Show detailed output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the Tauri-PHP development server';

    /**
     * The Laravel development server process.
     *
     * @var Process|null
     */
    protected $serverProcess;

    /**
     * The Tauri dev process.
     *
     * @var Process|null
     */
    protected $tauriProcess;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $this->info('🚀 Starting Tauri-PHP Development Mode');
            $this->newLine();

            // Step 1: Load configuration
            $envManager = new EnvTauriManager;

            if (! $envManager->exists()) {
                throw TauriPhpException::configurationError('Run tauri:init first to initialize the project');
            }

            $envManager->load();

            // Step 2: Get development server settings
            $host = $this->option('host');
            $port = $this->option('port');

            // Step 3: Update Tauri config for development
            $this->updateTauriConfig($envManager, $host, $port);

            // Step 4: Start Laravel development server
            $this->startLaravelServer($host, $port);

            // Step 5: Wait for server to be ready
            $this->waitForServer($host, $port);

            // Step 6: Start Tauri in dev mode
            $this->startTauriDev();

            // Wait for Tauri to exit
            $this->tauriProcess->wait();

            return Command::SUCCESS;
        } catch (TauriPhpException $e) {
            $this->error('❌ '.$e->getMessage());

            return Command::FAILURE;
        } catch (\Exception $e) {
            $this->error('❌ Unexpected error: '.$e->getMessage());

            if ($this->option('verbose')) {
                $this->error($e->getTraceAsString());
            }

            return Command::FAILURE;
        } finally {
            $this->cleanup();
        }
    }

    /**
     * Update Tauri configuration for development.
     *
     *
     * @throws TauriPhpException
     */
    protected function updateTauriConfig(EnvTauriManager $envManager, string $host, int $port): void
    {
        $this->line('⚙️  Updating Tauri configuration for development...');

        $configGenerator = new TauriConfigGenerator($envManager);
        $configGenerator->updateForDevelopment($host, $port);

        $this->line('  ✓ Configuration updated');
        $this->newLine();
    }

    /**
     * Start Laravel development server.
     */
    protected function startLaravelServer(string $host, int $port): void
    {
        $this->line('🌐 Starting Laravel development server...');

        $this->serverProcess = $this->runProcessAsync([
            'php',
            'artisan',
            'serve',
            "--host={$host}",
            "--port={$port}",
        ]);

        $this->line("  ✓ Server starting on http://{$host}:{$port}");
        $this->newLine();
    }

    /**
     * Wait for server to be ready.
     *
     *
     * @throws TauriPhpException
     */
    protected function waitForServer(string $host, int $port): void
    {
        $this->line('⏳ Waiting for server to be ready...');

        $url = "http://{$host}:{$port}";

        if (! $this->waitForUrl($url, 30, 1)) {
            throw TauriPhpException::processExecutionFailed(
                'Laravel server',
                'Server did not become ready in time'
            );
        }

        $this->line('  ✓ Server is ready');
        $this->newLine();
    }

    /**
     * Start Tauri in development mode.
     */
    protected function startTauriDev(): void
    {
        $this->line('🦀 Starting Tauri development mode...');
        $this->newLine();

        $this->tauriProcess = new Process(['npm', 'run', 'tauri', 'dev'], base_path());
        $this->tauriProcess->setTimeout(null);

        $this->tauriProcess->run(function ($type, $buffer) {
            echo $buffer;
        });
    }

    /**
     * Cleanup processes.
     */
    protected function cleanup(): void
    {
        $this->newLine();
        $this->line('🛑 Shutting down...');

        if ($this->serverProcess && $this->serverProcess->isRunning()) {
            $this->serverProcess->stop();
            $this->line('  ✓ Laravel server stopped');
        }

        if ($this->tauriProcess && $this->tauriProcess->isRunning()) {
            $this->tauriProcess->stop();
            $this->line('  ✓ Tauri stopped');
        }

        $this->newLine();
        $this->info('✅ Development mode stopped');
    }
}
