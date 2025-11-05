<?php

namespace Mucan54\TauriPhp\Console;

use Illuminate\Console\Command;
use Mucan54\TauriPhp\Exceptions\TauriPhpException;
use Mucan54\TauriPhp\Services\EnvTauriManager;
use Mucan54\TauriPhp\Traits\RunsProcesses;

class MobileInitCommand extends Command
{
    use RunsProcesses;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tauri:mobile-init
                            {platform : Mobile platform (android|ios|both)}
                            {--package-name= : Android package name or iOS bundle identifier}
                            {--team-id= : iOS team ID (required for iOS)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize Tauri for mobile platforms (Android/iOS)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $this->info('📱 Initializing Tauri Mobile Support');
            $this->newLine();

            // Validate that Tauri is already initialized
            if (! $this->isTauriInitialized()) {
                throw TauriPhpException::configurationError('Run tauri:init first to initialize the desktop project');
            }

            $platform = $this->argument('platform');

            // Validate prerequisites
            $this->validatePrerequisites($platform);

            // Initialize based on platform
            match ($platform) {
                'android' => $this->initializeAndroid(),
                'ios' => $this->initializeIos(),
                'both' => $this->initializeBoth(),
                default => throw TauriPhpException::configurationError("Invalid platform: {$platform}. Use 'android', 'ios', or 'both'"),
            };

            // Update .env.tauri with mobile settings
            $this->updateEnvTauri($platform);

            $this->newLine();
            $this->info('✅ Mobile initialization completed successfully!');
            $this->newLine();

            $this->displayNextSteps($platform);

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
     * Validate prerequisites for mobile development.
     *
     *
     * @throws TauriPhpException
     */
    protected function validatePrerequisites(string $platform): void
    {
        $this->line('📋 Validating mobile prerequisites...');

        if ($platform === 'android' || $platform === 'both') {
            $this->validateAndroidPrerequisites();
        }

        if ($platform === 'ios' || $platform === 'both') {
            $this->validateIosPrerequisites();
        }

        $this->newLine();
    }

    /**
     * Validate Android prerequisites.
     *
     *
     * @throws TauriPhpException
     */
    protected function validateAndroidPrerequisites(): void
    {
        // Check for Java/JDK
        if (! $this->commandExists('java')) {
            throw TauriPhpException::prerequisiteMissing(
                'Java JDK',
                'Install Java JDK 11 or higher from https://adoptium.net/'
            );
        }

        $javaVersion = $this->getCommandVersion('java', '-version');
        $this->line("  ✓ Java: {$javaVersion}");

        // Check for Android SDK
        $androidHome = getenv('ANDROID_HOME') ?: getenv('ANDROID_SDK_ROOT');

        if (! $androidHome || ! is_dir($androidHome)) {
            $this->warn('  ⚠ ANDROID_HOME not set. Please install Android SDK and set ANDROID_HOME environment variable.');
            $this->line('    Download from: https://developer.android.com/studio');
        } else {
            $this->line("  ✓ Android SDK: {$androidHome}");
        }

        // Check for NDK
        if ($androidHome && is_dir($androidHome.'/ndk')) {
            $this->line('  ✓ Android NDK: Found');
        } else {
            $this->warn('  ⚠ Android NDK not found. Install via Android Studio SDK Manager.');
        }
    }

    /**
     * Validate iOS prerequisites.
     *
     *
     * @throws TauriPhpException
     */
    protected function validateIosPrerequisites(): void
    {
        // iOS development only on macOS
        if (PHP_OS_FAMILY !== 'Darwin') {
            throw TauriPhpException::prerequisiteMissing(
                'macOS',
                'iOS development requires macOS'
            );
        }

        // Check for Xcode
        if (! $this->commandExists('xcodebuild')) {
            throw TauriPhpException::prerequisiteMissing(
                'Xcode',
                'Install Xcode from the Mac App Store'
            );
        }

        $xcodeVersion = $this->getCommandVersion('xcodebuild', '-version');
        $this->line("  ✓ Xcode: {$xcodeVersion}");

        // Check for CocoaPods
        if (! $this->commandExists('pod')) {
            $this->warn('  ⚠ CocoaPods not found. Install with: sudo gem install cocoapods');
        } else {
            $podVersion = $this->getCommandVersion('pod', '--version');
            $this->line("  ✓ CocoaPods: {$podVersion}");
        }
    }

    /**
     * Initialize Android platform.
     *
     *
     * @throws TauriPhpException
     */
    protected function initializeAndroid(): void
    {
        $this->line('🤖 Initializing Android...');

        $this->runProcess(
            ['npm', 'run', 'tauri', 'android', 'init'],
            'Setting up Android project...',
            600
        );

        $this->line('  ✓ Android initialized');
        $this->newLine();
    }

    /**
     * Initialize iOS platform.
     *
     *
     * @throws TauriPhpException
     */
    protected function initializeIos(): void
    {
        $this->line('🍎 Initializing iOS...');

        $teamId = $this->option('team-id');

        if (! $teamId) {
            throw TauriPhpException::configurationError('iOS requires --team-id option. Get your Team ID from https://developer.apple.com/account');
        }

        $this->runProcess(
            ['npm', 'run', 'tauri', 'ios', 'init'],
            'Setting up iOS project...',
            600
        );

        $this->line('  ✓ iOS initialized');
        $this->newLine();
    }

    /**
     * Initialize both platforms.
     */
    protected function initializeBoth(): void
    {
        $this->initializeAndroid();
        $this->initializeIos();
    }

    /**
     * Update .env.tauri with mobile settings.
     *
     *
     * @throws TauriPhpException
     */
    protected function updateEnvTauri(string $platform): void
    {
        $this->line('📝 Updating .env.tauri...');

        $envManager = new EnvTauriManager;
        $packageName = $this->option('package-name');

        if (! $packageName) {
            $appIdentifier = $envManager->get('TAURI_APP_IDENTIFIER', 'com.example.myapp');
            $packageName = $appIdentifier;
        }

        if ($platform === 'android' || $platform === 'both') {
            $envManager->set('TAURI_ANDROID_PACKAGE_NAME', $packageName);
        }

        if ($platform === 'ios' || $platform === 'both') {
            $envManager->set('TAURI_IOS_BUNDLE_IDENTIFIER', $packageName);

            if ($teamId = $this->option('team-id')) {
                $envManager->set('TAURI_IOS_TEAM_ID', $teamId);
            }
        }

        $this->line('  ✓ Configuration updated');
        $this->newLine();
    }

    /**
     * Check if Tauri is initialized.
     */
    protected function isTauriInitialized(): bool
    {
        return file_exists(base_path('src-tauri/Cargo.toml'));
    }

    /**
     * Display next steps.
     */
    protected function displayNextSteps(string $platform): void
    {
        $this->info('Next Steps:');

        if ($platform === 'android' || $platform === 'both') {
            $this->line('  Android:');
            $this->line('    1. Connect an Android device or start an emulator');
            $this->line('    2. Run: php artisan tauri:mobile-dev android');
            $this->line('    3. Build: php artisan tauri:build --platform=android');
            $this->newLine();
        }

        if ($platform === 'ios' || $platform === 'both') {
            $this->line('  iOS:');
            $this->line('    1. Open Xcode and configure signing');
            $this->line('    2. Connect an iOS device or start a simulator');
            $this->line('    3. Run: php artisan tauri:mobile-dev ios');
            $this->line('    4. Build: php artisan tauri:build --platform=ios');
            $this->newLine();
        }

        $this->line('For more information, visit: https://v2.tauri.app/develop/');
    }
}
