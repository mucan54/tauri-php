<?php

namespace Mucan54\TauriPhp\Console;

use Illuminate\Console\Command;
use Mucan54\TauriPhp\Exceptions\TauriPhpException;
use Mucan54\TauriPhp\Traits\RunsProcesses;
use Symfony\Component\Process\Process;

class MobileDevCommand extends Command
{
    use RunsProcesses;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tauri:mobile-dev
                            {platform : Mobile platform (android|ios)}
                            {--device= : Specific device to run on}
                            {--emulator : Run on emulator/simulator instead of physical device}
                            {--host=0.0.0.0 : Development server host (0.0.0.0 for mobile access)}
                            {--port=8080 : Development server port}
                            {--verbose : Show detailed output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the Tauri-PHP app on mobile device/emulator';

    /**
     * The Laravel development server process.
     *
     * @var Process|null
     */
    protected $serverProcess;

    /**
     * The Tauri mobile process.
     *
     * @var Process|null
     */
    protected $mobileProcess;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $platform = $this->argument('platform');

            $this->info("📱 Starting Tauri-PHP Mobile Development Mode ({$platform})");
            $this->newLine();

            // Validate platform
            if (! in_array($platform, ['android', 'ios'])) {
                throw TauriPhpException::configurationError("Invalid platform: {$platform}. Use 'android' or 'ios'");
            }

            // Validate mobile is initialized
            if (! $this->isMobileInitialized($platform)) {
                throw TauriPhpException::configurationError("Mobile platform not initialized. Run: php artisan tauri:mobile-init {$platform}");
            }

            // Get development server settings
            $host = $this->option('host');
            $port = $this->option('port');

            // Start Laravel development server
            $this->startLaravelServer($host, $port);

            // Wait for server to be ready
            $this->waitForServer($host, $port);

            // Display server URL for mobile access
            $this->displayServerInfo($host, $port);

            // Start mobile development
            $this->startMobileDev($platform);

            // Wait for mobile process to exit
            $this->mobileProcess->wait();

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
     * Check if mobile platform is initialized.
     */
    protected function isMobileInitialized(string $platform): bool
    {
        $paths = [
            'android' => 'src-tauri/gen/android',
            'ios' => 'src-tauri/gen/apple',
        ];

        return is_dir(base_path($paths[$platform]));
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
     * Display server information for mobile access.
     */
    protected function displayServerInfo(string $host, int $port): void
    {
        $this->line('📡 Server Information:');
        $this->line("  • Host: {$host}");
        $this->line("  • Port: {$port}");

        // Try to get local IP for mobile access
        if ($host === '0.0.0.0') {
            $localIp = $this->getLocalIp();

            if ($localIp) {
                $this->line("  • Mobile URL: http://{$localIp}:{$port}");
                $this->warn('  Make sure your mobile device is on the same network!');
            }
        }

        $this->newLine();
    }

    /**
     * Get local IP address.
     */
    protected function getLocalIp(): ?string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $output = shell_exec('ipconfig');

            if (preg_match('/IPv4 Address[^\d]+([\d.]+)/', $output, $matches)) {
                return $matches[1];
            }
        } else {
            $output = shell_exec('hostname -I');

            if ($output) {
                $ips = explode(' ', trim($output));

                return $ips[0] ?? null;
            }
        }

        return null;
    }

    /**
     * Start mobile development.
     */
    protected function startMobileDev(string $platform): void
    {
        $this->line("📱 Starting {$platform} development...");
        $this->newLine();

        $command = ['npm', 'run', 'tauri', $platform, 'dev'];

        // Add device option if specified
        if ($device = $this->option('device')) {
            $command[] = '--';
            $command[] = '--device';
            $command[] = $device;
        }

        // Add open flag for emulator/simulator
        if ($this->option('emulator')) {
            if ($platform === 'android') {
                $this->info('Starting Android emulator...');
                $this->startAndroidEmulator();
            } elseif ($platform === 'ios') {
                $this->info('Starting iOS simulator...');
                // iOS simulator is started automatically by Tauri
            }
        }

        $this->mobileProcess = new Process($command, base_path());
        $this->mobileProcess->setTimeout(null);

        $this->mobileProcess->run(function ($type, $buffer) {
            echo $buffer;
        });
    }

    /**
     * Start Android emulator.
     */
    protected function startAndroidEmulator(): void
    {
        try {
            // List available emulators
            $process = new Process(['emulator', '-list-avds']);
            $process->run();

            if (! $process->isSuccessful() || empty(trim($process->getOutput()))) {
                $this->warn('No Android emulators found. Create one in Android Studio AVD Manager.');

                return;
            }

            $avds = array_filter(explode("\n", trim($process->getOutput())));

            if (empty($avds)) {
                return;
            }

            // Start first available emulator in background
            $emulatorName = $avds[0];
            $this->line("  Starting emulator: {$emulatorName}");

            $emulatorProcess = new Process(['emulator', '-avd', $emulatorName]);
            $emulatorProcess->setTimeout(null);
            $emulatorProcess->start();

            sleep(5); // Give emulator time to start
        } catch (\Exception $e) {
            $this->warn('  Could not start emulator automatically. Please start it manually.');
        }
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

        if ($this->mobileProcess && $this->mobileProcess->isRunning()) {
            $this->mobileProcess->stop();
            $this->line('  ✓ Mobile development stopped');
        }

        $this->newLine();
        $this->info('✅ Mobile development mode stopped');
    }
}
