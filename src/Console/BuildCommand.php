<?php

namespace Mucan54\TauriPhp\Console;

use Illuminate\Console\Command;
use Mucan54\TauriPhp\Exceptions\TauriPhpException;
use Mucan54\TauriPhp\Services\CodeObfuscator;
use Mucan54\TauriPhp\Services\EnvTauriManager;
use Mucan54\TauriPhp\Services\FrankenPhpBuilder;
use Mucan54\TauriPhp\Services\TauriConfigGenerator;
use Mucan54\TauriPhp\Services\TauriPhpService;
use Mucan54\TauriPhp\Traits\RunsProcesses;

class BuildCommand extends Command
{
    use RunsProcesses;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tauri:build
                            {--platform=all : Target platform (desktop: linux-x64|macos-x64|windows-x64|all, mobile: android|ios)}
                            {--obfuscate : Obfuscate PHP code}
                            {--debug : Build in debug mode}
                            {--skip-deps : Skip installing dependencies}
                            {--force : Force rebuild of FrankenPHP binaries}
                            {--apk : Build APK for Android (default is AAB)}
                            {--open : Open in Xcode after build (iOS only)}
                            {--verbose : Show detailed output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build the Tauri-PHP application for desktop or mobile';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        try {
            $platform = $this->option('platform');

            // Check if building for mobile
            if (in_array($platform, ['android', 'ios'])) {
                return $this->handleMobileBuild($platform);
            }

            $this->info('🔨 Building Tauri-PHP Desktop Application');
            $this->newLine();

            // Step 1: Load configuration
            $envManager = new EnvTauriManager();

            if (!$envManager->exists()) {
                throw TauriPhpException::configurationError('Run tauri:init first to initialize the project');
            }

            $envManager->load();

            // Step 2: Determine platforms
            $platforms = $this->resolvePlatforms();

            $this->info('Building for platforms: '.implode(', ', array_keys($platforms)));
            $this->newLine();

            // Step 3: Install dependencies
            if (!$this->option('skip-deps')) {
                $this->installDependencies();
            }

            // Step 4: Optimize Laravel
            $this->optimizeLaravel();

            // Step 5: Prepare embedded app
            $this->prepareEmbeddedApp();

            // Step 6: Obfuscate code (if requested)
            if ($this->option('obfuscate')) {
                $this->obfuscateCode();
            }

            // Step 7: Build FrankenPHP binaries
            $this->buildFrankenPhpBinaries($platforms, $envManager);

            // Step 8: Update Tauri configuration
            $this->updateTauriConfig($envManager);

            // Step 9: Build Tauri application
            $this->buildTauriApp();

            // Step 10: Display results
            $this->displayBuildResults();

            $this->newLine();
            $this->info('✅ Build completed successfully!');

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
        }
    }

    /**
     * Resolve target platforms.
     *
     * @return array
     */
    protected function resolvePlatforms(): array
    {
        $allPlatforms = config('tauri-php.platforms', [
            'linux-x64' => 'x86_64-unknown-linux-gnu',
            'linux-arm64' => 'aarch64-unknown-linux-gnu',
            'macos-x64' => 'x86_64-apple-darwin',
            'macos-arm64' => 'aarch64-apple-darwin',
            'windows-x64' => 'x86_64-pc-windows-msvc',
        ]);

        $requestedPlatform = $this->option('platform');

        if ($requestedPlatform === 'all') {
            return $allPlatforms;
        }

        if (!isset($allPlatforms[$requestedPlatform])) {
            $this->warn("Unknown platform: {$requestedPlatform}. Building for all platforms.");

            return $allPlatforms;
        }

        return [$requestedPlatform => $allPlatforms[$requestedPlatform]];
    }

    /**
     * Install dependencies.
     *
     * @return void
     *
     * @throws TauriPhpException
     */
    protected function installDependencies(): void
    {
        $this->line('📦 Installing dependencies...');

        $this->runProcess(
            ['composer', 'install', '--no-dev', '--optimize-autoloader', '--no-interaction'],
            'Installing PHP dependencies...'
        );

        $this->runProcess(
            ['npm', 'ci', '--production'],
            'Installing Node.js dependencies...'
        );

        $this->newLine();
    }

    /**
     * Optimize Laravel application.
     *
     * @return void
     *
     * @throws TauriPhpException
     */
    protected function optimizeLaravel(): void
    {
        $this->line('⚡ Optimizing Laravel...');

        $commands = [
            ['php', 'artisan', 'config:cache'],
            ['php', 'artisan', 'route:cache'],
            ['php', 'artisan', 'view:cache'],
        ];

        foreach ($commands as $command) {
            $this->runProcess($command, 'Running '.implode(' ', $command).'...');
        }

        $this->newLine();
    }

    /**
     * Prepare embedded application.
     *
     * @return void
     *
     * @throws TauriPhpException
     */
    protected function prepareEmbeddedApp(): void
    {
        $this->line('📦 Preparing embedded application...');

        $service = new TauriPhpService();
        $service->prepareEmbeddedApp();

        $this->line('  ✓ Laravel app prepared for embedding');
        $this->newLine();
    }

    /**
     * Obfuscate PHP code.
     *
     * @return void
     *
     * @throws TauriPhpException
     */
    protected function obfuscateCode(): void
    {
        $this->line('🔒 Obfuscating PHP code...');

        $obfuscator = new CodeObfuscator();
        $obfuscator->obfuscate();

        $this->line('  ✓ Code obfuscated successfully');
        $this->newLine();
    }

    /**
     * Build FrankenPHP binaries for all platforms.
     *
     * @param  array  $platforms
     * @param  EnvTauriManager  $envManager
     * @return void
     *
     * @throws TauriPhpException
     */
    protected function buildFrankenPhpBinaries(array $platforms, EnvTauriManager $envManager): void
    {
        $this->line('🚀 Building FrankenPHP binaries...');

        $builder = new FrankenPhpBuilder($envManager);

        foreach ($platforms as $platform => $targetTriple) {
            $this->line("  → Building for {$platform}...");

            $options = [
                'force' => $this->option('force'),
                'verbose' => $this->option('verbose'),
            ];

            try {
                $binaryPath = $builder->build($platform, $targetTriple, $options);

                if ($builder->verifyBinary($binaryPath)) {
                    $this->line("    ✓ Built: {$binaryPath}");
                } else {
                    $this->warn("    ⚠ Binary verification failed: {$binaryPath}");
                }
            } catch (\Exception $e) {
                $this->error("    ✗ Failed to build for {$platform}: ".$e->getMessage());

                if ($this->option('verbose')) {
                    $this->error($e->getTraceAsString());
                }
            }
        }

        $this->newLine();
    }

    /**
     * Update Tauri configuration for production.
     *
     * @param  EnvTauriManager  $envManager
     * @return void
     *
     * @throws TauriPhpException
     */
    protected function updateTauriConfig(EnvTauriManager $envManager): void
    {
        $this->line('⚙️  Updating Tauri configuration...');

        $configGenerator = new TauriConfigGenerator($envManager);
        $configGenerator->updateForProduction();

        $this->line('  ✓ Configuration updated');
        $this->newLine();
    }

    /**
     * Build Tauri application.
     *
     * @return void
     *
     * @throws TauriPhpException
     */
    protected function buildTauriApp(): void
    {
        $this->line('🦀 Building Tauri application...');

        $command = ['npm', 'run', 'tauri'];

        if ($this->option('debug')) {
            $command[] = 'build';
        } else {
            $command[] = 'build';
            $command[] = '--';
            $command[] = '--release';
        }

        $this->runProcess($command, 'Building Tauri...', 1800);

        $this->line('  ✓ Tauri build completed');
        $this->newLine();
    }

    /**
     * Display build results.
     *
     * @return void
     */
    protected function displayBuildResults(): void
    {
        $this->line('📁 Build artifacts:');

        $bundleDir = base_path('src-tauri/target/release/bundle');

        if (!is_dir($bundleDir)) {
            $this->warn('  No build artifacts found');

            return;
        }

        $artifacts = $this->findArtifacts($bundleDir);

        if (empty($artifacts)) {
            $this->warn('  No build artifacts found');

            return;
        }

        foreach ($artifacts as $artifact) {
            $size = $this->formatFileSize(filesize($artifact));
            $relativePath = str_replace(base_path().'/', '', $artifact);
            $this->line("  • {$relativePath} ({$size})");
        }

        $this->newLine();
    }

    /**
     * Find build artifacts recursively.
     *
     * @param  string  $directory
     * @return array
     */
    protected function findArtifacts(string $directory): array
    {
        $artifacts = [];
        $extensions = ['exe', 'dmg', 'app', 'deb', 'AppImage', 'msi'];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $ext = $file->getExtension();

                if (in_array($ext, $extensions) || str_ends_with($file->getFilename(), '.app')) {
                    $artifacts[] = $file->getPathname();
                }
            }
        }

        return $artifacts;
    }

    /**
     * Format file size.
     *
     * @param  int  $bytes
     * @return string
     */
    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $index = 0;

        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        return round($bytes, 2).' '.$units[$index];
    }

    /**
     * Handle mobile platform build.
     *
     * @param  string  $platform
     * @return int
     *
     * @throws TauriPhpException
     */
    protected function handleMobileBuild(string $platform): int
    {
        $this->info("📱 Building Tauri-PHP Mobile Application ({$platform})");
        $this->newLine();

        // Validate mobile is initialized
        if (!$this->isMobileInitialized($platform)) {
            throw TauriPhpException::configurationError(
                "Mobile platform not initialized. Run: php artisan tauri:mobile-init {$platform}"
            );
        }

        // Load configuration
        $envManager = new EnvTauriManager();

        if (!$envManager->exists()) {
            throw TauriPhpException::configurationError('Run tauri:init first to initialize the project');
        }

        $envManager->load();

        // Install dependencies
        if (!$this->option('skip-deps')) {
            $this->installDependencies();
        }

        // Optimize Laravel
        $this->optimizeLaravel();

        // Prepare embedded app
        $this->prepareEmbeddedApp();

        // Obfuscate code (if requested)
        if ($this->option('obfuscate')) {
            $this->obfuscateCode();
        }

        // Build mobile application
        $this->buildMobileApp($platform);

        $this->newLine();
        $this->info('✅ Mobile build completed successfully!');

        $this->displayMobileBuildResults($platform);

        return Command::SUCCESS;
    }

    /**
     * Check if mobile platform is initialized.
     *
     * @param  string  $platform
     * @return bool
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
     * Build mobile application.
     *
     * @param  string  $platform
     * @return void
     *
     * @throws TauriPhpException
     */
    protected function buildMobileApp(string $platform): void
    {
        $this->line("📱 Building {$platform} application...");

        $command = ['npm', 'run', 'tauri', $platform, 'build'];

        // Add platform-specific flags
        if ($platform === 'android') {
            if ($this->option('apk')) {
                $command[] = '--';
                $command[] = '--apk';
            }

            if ($this->option('debug')) {
                $command[] = '--';
                $command[] = '--debug';
            }
        } elseif ($platform === 'ios') {
            if ($this->option('open')) {
                $command[] = '--';
                $command[] = '--open';
            }
        }

        $this->runProcess($command, "Building {$platform} app...", 1800);

        $this->line("  ✓ {$platform} build completed");
        $this->newLine();
    }

    /**
     * Display mobile build results.
     *
     * @param  string  $platform
     * @return void
     */
    protected function displayMobileBuildResults(string $platform): void
    {
        $this->line('📁 Build artifacts:');

        if ($platform === 'android') {
            $outputDir = base_path('src-tauri/gen/android/app/build/outputs');

            if (is_dir($outputDir)) {
                $apks = glob($outputDir.'/apk/**/*.apk');
                $aabs = glob($outputDir.'/bundle/**/*.aab');

                foreach (array_merge($apks, $aabs) as $artifact) {
                    $size = $this->formatFileSize(filesize($artifact));
                    $relativePath = str_replace(base_path().'/', '', $artifact);
                    $this->line("  • {$relativePath} ({$size})");
                }
            } else {
                $this->warn('  No build artifacts found');
            }
        } elseif ($platform === 'ios') {
            $outputDir = base_path('src-tauri/gen/apple/build/Release-iphoneos');

            if (is_dir($outputDir)) {
                $apps = glob($outputDir.'/*.app');

                foreach ($apps as $app) {
                    $relativePath = str_replace(base_path().'/', '', $app);
                    $this->line("  • {$relativePath}");
                }
            } else {
                $this->warn('  No build artifacts found');
            }
        }

        $this->newLine();
    }
}
