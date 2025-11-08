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
                            {--port=8080 : Development server port}';

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

            // Check and build PHP binary if missing
            $this->ensurePhpBinaryExists($platform);

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
     * Ensure PHP binary exists for the platform, build if missing.
     *
     * @throws TauriPhpException
     */
    protected function ensurePhpBinaryExists(string $platform): void
    {
        $binaryPath = $this->getPhpBinaryPath($platform);

        if (file_exists($binaryPath)) {
            $this->line("✓ PHP binary found: {$binaryPath}");
            $this->newLine();

            return;
        }

        $this->warn("⚠️  PHP binary not found for {$platform}");
        $this->info("Building PHP binary automatically...");
        $this->newLine();

        $this->buildPhpBinary($platform);

        // Verify build succeeded
        if (! file_exists($binaryPath)) {
            throw TauriPhpException::configurationError(
                "PHP binary build failed. Binary not found at: {$binaryPath}"
            );
        }

        $this->info("✅ PHP binary built successfully!");
        $this->newLine();
    }

    /**
     * Get the expected PHP binary path for the platform.
     */
    protected function getPhpBinaryPath(string $platform): string
    {
        $binariesDir = base_path('binaries');
        $binaries = [
            'ios' => 'php-iphonesimulator-arm64', // Use simulator binary for dev
            'android' => 'php-android-aarch64',
        ];

        return "{$binariesDir}/{$binaries[$platform]}";
    }

    /**
     * Build PHP binary for the platform.
     *
     * @throws TauriPhpException
     */
    protected function buildPhpBinary(string $platform): void
    {
        if ($platform === 'ios') {
            $this->buildPhpForIos();
        } elseif ($platform === 'android') {
            $this->buildPhpForAndroid();
        }
    }

    /**
     * Build PHP for iOS.
     *
     * @throws TauriPhpException
     */
    protected function buildPhpForIos(): void
    {
        // Check if running on macOS
        if (PHP_OS_FAMILY !== 'Darwin') {
            throw TauriPhpException::configurationError(
                'iOS PHP binary compilation requires macOS. Please build on a Mac or use pre-built binaries.'
            );
        }

        // Check if Xcode is installed
        $xcodePath = trim(shell_exec('xcode-select -p 2>/dev/null') ?? '');
        if (empty($xcodePath) || ! is_dir($xcodePath)) {
            throw TauriPhpException::configurationError(
                'Xcode is not installed. Install Xcode from the App Store and run: sudo xcode-select --switch /Applications/Xcode.app'
            );
        }

        // Find the build script
        $scriptPath = $this->findBuildScript('build-php-ios.sh');

        if (! $scriptPath) {
            throw TauriPhpException::configurationError(
                'Build script not found. Expected: vendor/mucan54/tauri-php/scripts/build-php-ios.sh'
            );
        }

        $this->line("📦 Building PHP for iOS (this may take 15-30 minutes)...");
        $this->line("  Script: {$scriptPath}");
        $this->newLine();

        // Make script executable
        chmod($scriptPath, 0755);

        // Run the build script
        $process = new Process(['bash', $scriptPath], dirname($scriptPath));
        $process->setTimeout(3600); // 1 hour timeout

        $this->info('Build started...');
        $this->newLine();

        $process->run(function ($type, $buffer) {
            // Show output to user
            echo $buffer;
        });

        if (! $process->isSuccessful()) {
            throw TauriPhpException::processExecutionFailed(
                'PHP build script',
                $process->getErrorOutput()
            );
        }

        // Copy binaries to project root binaries directory
        $this->copyPhpBinariesToProject();
    }

    /**
     * Build PHP for Android.
     *
     * @throws TauriPhpException
     */
    protected function buildPhpForAndroid(): void
    {
        $scriptPath = $this->findBuildScript('build-php-android.sh');

        if (! $scriptPath) {
            throw TauriPhpException::configurationError(
                "Android build script not yet implemented. \n".
                "Please check documentation for manual build instructions or wait for v1.4.0."
            );
        }

        // Similar implementation as iOS when Android script is ready
        $this->warn('Android automatic build not yet implemented.');
        $this->warn('Please build manually or wait for v1.4.0 release.');

        throw TauriPhpException::configurationError('Android PHP binary build not available yet.');
    }

    /**
     * Find the build script in package or vendor directory.
     */
    protected function findBuildScript(string $scriptName): ?string
    {
        $possiblePaths = [
            base_path("scripts/{$scriptName}"), // In project root (if package is being developed)
            base_path("vendor/mucan54/tauri-php/scripts/{$scriptName}"), // In vendor
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Copy PHP binaries from vendor to project binaries directory.
     */
    protected function copyPhpBinariesToProject(): void
    {
        $vendorBinaries = base_path('vendor/mucan54/tauri-php/binaries');
        $projectBinaries = base_path('binaries');

        // Create project binaries directory if it doesn't exist
        if (! is_dir($projectBinaries)) {
            mkdir($projectBinaries, 0755, true);
        }

        // Check if build script created binaries in vendor
        if (is_dir($vendorBinaries)) {
            $files = glob("{$vendorBinaries}/php-*");

            foreach ($files as $file) {
                $filename = basename($file);
                $destination = "{$projectBinaries}/{$filename}";

                if (copy($file, $destination)) {
                    $this->line("  ✓ Copied {$filename} to project binaries/");
                }
            }
        }

        // Also check if binaries were created in scripts/../binaries
        $scriptBinaries = dirname($this->findBuildScript('build-php-ios.sh') ?? '') . '/../binaries';
        if (is_dir($scriptBinaries)) {
            $files = glob("{$scriptBinaries}/php-*");

            foreach ($files as $file) {
                $filename = basename($file);
                $destination = "{$projectBinaries}/{$filename}";

                if (! file_exists($destination) && copy($file, $destination)) {
                    $this->line("  ✓ Copied {$filename} to project binaries/");
                }
            }
        }
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
        } elseif (PHP_OS_FAMILY === 'Darwin') {
            // macOS - try common network interfaces
            foreach (['en0', 'en1'] as $interface) {
                $output = shell_exec("ipconfig getifaddr {$interface} 2>/dev/null");
                if ($output && trim($output)) {
                    return trim($output);
                }
            }

            // Fallback: parse ifconfig output
            $output = shell_exec('ifconfig | grep "inet " | grep -v 127.0.0.1 | head -n1');
            if ($output && preg_match('/inet ([\d.]+)/', $output, $matches)) {
                return $matches[1];
            }
        } else {
            // Linux
            $output = shell_exec('hostname -I 2>/dev/null');

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

        $command = ['npm', 'run', 'tauri', '--', $platform, 'dev'];

        // Handle device/emulator selection
        if ($device = $this->option('device')) {
            // Device specified directly - use it as positional argument
            $command[] = $device;
        } elseif ($this->option('emulator')) {
            // Emulator flag - automatically select a simulator
            if ($platform === 'android') {
                $this->info('Starting Android emulator...');
                $this->startAndroidEmulator();
            } elseif ($platform === 'ios') {
                $this->info('Starting iOS simulator...');
                // Get available simulators and pick the first booted one or default
                $simulator = $this->getAvailableIosSimulator();
                if ($simulator) {
                    $this->line("  Using simulator: {$simulator}");
                    $command[] = $simulator;
                }
            }
        }

        $this->mobileProcess = new Process($command, base_path());
        $this->mobileProcess->setTimeout(null);

        $this->mobileProcess->run(function ($type, $buffer) {
            echo $buffer;
        });
    }

    /**
     * Get an available iOS simulator.
     */
    protected function getAvailableIosSimulator(): ?string
    {
        try {
            $process = new Process(['xcrun', 'simctl', 'list', 'devices', 'available', 'iPhone']);
            $process->run();

            if (! $process->isSuccessful()) {
                return null;
            }

            $output = $process->getOutput();

            // Try to find iPhone 15 or any recent iPhone
            if (preg_match('/iPhone \d+( Pro| Plus)?\s+\(([A-F0-9-]+)\)\s+\(Booted\)/i', $output, $matches)) {
                return $matches[2]; // Return booted simulator UUID
            }

            // If no booted simulator, find any available one
            if (preg_match('/iPhone \d+( Pro| Plus)?\s+\(([A-F0-9-]+)\)/i', $output, $matches)) {
                return $matches[2]; // Return simulator UUID
            }
        } catch (\Exception $e) {
            // Fallback - let Tauri handle it
        }

        return null;
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
