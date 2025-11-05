<?php

namespace Mucan54\TauriPhp\Console;

use Illuminate\Console\Command;
use Mucan54\TauriPhp\Exceptions\TauriPhpException;
use Mucan54\TauriPhp\Services\EnvTauriManager;
use Mucan54\TauriPhp\Services\StubManager;
use Mucan54\TauriPhp\Services\TauriConfigGenerator;
use Mucan54\TauriPhp\Traits\RunsProcesses;

class InitCommand extends Command
{
    use RunsProcesses;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tauri:init
                            {--template=vanilla : Frontend template (vue|react|svelte|vanilla)}
                            {--identifier= : Application identifier (com.company.app)}
                            {--php-version=8.3 : PHP version}
                            {--extensions= : PHP extensions (comma-separated)}
                            {--force : Overwrite existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize a new Tauri-PHP desktop application';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $this->info('🚀 Initializing Tauri-PHP Desktop Application');
            $this->newLine();

            // Step 1: Validate prerequisites
            $this->validatePrerequisites();

            // Step 2: Check if already initialized
            if ($this->isTauriInitialized() && ! $this->option('force')) {
                $this->warn('Tauri is already initialized. Use --force to reinitialize.');

                return Command::FAILURE;
            }

            // Step 3: Create .env.tauri
            $this->createEnvTauri();

            // Step 4: Create directory structure
            $this->createDirectoryStructure();

            // Step 5: Install Node.js dependencies
            $this->installNodeDependencies();

            // Step 6: Initialize Tauri
            $this->initializeTauri();

            // Step 7: Setup Rust backend
            $this->setupRustBackend();

            // Step 8: Setup frontend
            $this->setupFrontend();

            // Step 9: Create build scripts
            $this->createBuildScripts();

            // Step 10: Generate Tauri configuration
            $this->generateTauriConfig();

            $this->newLine();
            $this->info('✅ Tauri-PHP initialization completed successfully!');
            $this->newLine();

            $this->displayNextSteps();

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
     * Validate prerequisites.
     *
     *
     * @throws TauriPhpException
     */
    protected function validatePrerequisites(): void
    {
        $this->line('📋 Validating prerequisites...');

        $prerequisites = [
            'node' => 'Node.js (https://nodejs.org/)',
            'npm' => 'npm (comes with Node.js)',
            'cargo' => 'Rust and Cargo (https://rustup.rs/)',
        ];

        foreach ($prerequisites as $command => $name) {
            if (! $this->commandExists($command)) {
                throw TauriPhpException::prerequisiteMissing($name, "Please install {$name}");
            }

            $version = $this->getCommandVersion($command);
            $this->line("  ✓ {$name}: {$version}");
        }

        $this->newLine();
    }

    /**
     * Create .env.tauri configuration file.
     *
     *
     * @throws TauriPhpException
     */
    protected function createEnvTauri(): void
    {
        $this->line('📝 Creating .env.tauri configuration...');

        $appName = config('app.name', 'My Desktop App');
        $envManager = new EnvTauriManager;

        $options = [
            'identifier' => $this->option('identifier') ?: $this->generateIdentifier($appName),
            'php_version' => $this->option('php-version'),
            'php_extensions' => $this->option('extensions') ?: 'opcache,pdo_sqlite,mbstring,openssl,tokenizer,xml,ctype,json,bcmath,fileinfo',
        ];

        $envManager->createFromTemplate($appName, $options);

        $this->line('  ✓ Created .env.tauri');
        $this->newLine();
    }

    /**
     * Create necessary directories.
     */
    protected function createDirectoryStructure(): void
    {
        $this->line('📁 Creating directory structure...');

        $directories = [
            'src-tauri/src',
            'src-tauri/capabilities',
            'desktop-frontend',
            'binaries',
            'tauri-temp',
        ];

        foreach ($directories as $dir) {
            $path = base_path($dir);

            if (! is_dir($path)) {
                mkdir($path, 0755, true);
                $this->line("  ✓ Created {$dir}");
            }
        }

        $this->newLine();
    }

    /**
     * Install Node.js dependencies.
     *
     *
     * @throws TauriPhpException
     */
    protected function installNodeDependencies(): void
    {
        $this->line('📦 Installing Node.js dependencies...');

        // Create package.json if it doesn't exist
        $packageJsonPath = base_path('package.json');

        if (! file_exists($packageJsonPath)) {
            $stubManager = new StubManager;
            $stubManager->copy('package.json', $packageJsonPath);
        }

        $this->runProcess(
            ['npm', 'install'],
            'Installing packages...'
        );

        $this->newLine();
    }

    /**
     * Initialize Tauri CLI.
     *
     *
     * @throws TauriPhpException
     */
    protected function initializeTauri(): void
    {
        $this->line('🦀 Initializing Tauri...');

        // Tauri CLI is installed as a dev dependency
        // We'll manually create the Tauri structure from stubs

        $this->newLine();
    }

    /**
     * Setup Rust backend from stubs.
     *
     *
     * @throws TauriPhpException
     */
    protected function setupRustBackend(): void
    {
        $this->line('🦀 Setting up Rust backend...');

        $stubManager = new StubManager;
        $envManager = new EnvTauriManager;
        $env = $envManager->all();

        $appName = $env['TAURI_APP_NAME'] ?? 'My Desktop App';
        $sanitizedName = strtolower(str_replace(' ', '-', $appName));

        $replacements = [
            'APP_NAME' => $appName,
            'APP_NAME_SANITIZED' => $sanitizedName,
        ];

        // Copy Rust files
        $stubManager->copy('tauri/src-tauri/src/main.rs', base_path('src-tauri/src/main.rs'), $replacements);
        $stubManager->copy('tauri/src-tauri/Cargo.toml', base_path('src-tauri/Cargo.toml'), $replacements);
        $stubManager->copy('tauri/src-tauri/build.rs', base_path('src-tauri/build.rs'), $replacements);
        $stubManager->copy('tauri/src-tauri/capabilities/default.json', base_path('src-tauri/capabilities/default.json'), $replacements);

        $this->line('  ✓ Created Rust source files');
        $this->newLine();
    }

    /**
     * Setup frontend files.
     *
     *
     * @throws TauriPhpException
     */
    protected function setupFrontend(): void
    {
        $this->line('🎨 Setting up frontend...');

        $template = $this->option('template');
        $stubManager = new StubManager;
        $envManager = new EnvTauriManager;
        $env = $envManager->all();

        $replacements = [
            'APP_NAME' => $env['TAURI_APP_NAME'] ?? 'My Desktop App',
            'DEV_HOST' => $env['TAURI_DEV_HOST'] ?? '127.0.0.1',
            'DEV_PORT' => $env['TAURI_DEV_PORT'] ?? '8080',
        ];

        // Copy frontend HTML
        $stubManager->copy('frontend/index.html', base_path('desktop-frontend/index.html'), $replacements);

        $this->line("  ✓ Created {$template} frontend");
        $this->newLine();
    }

    /**
     * Create build scripts.
     *
     *
     * @throws TauriPhpException
     */
    protected function createBuildScripts(): void
    {
        $this->line('📜 Creating build scripts...');

        $stubManager = new StubManager;

        $stubManager->copy('scripts/build.sh', base_path('build.sh'));
        $stubManager->copy('scripts/docker-build.sh', base_path('docker-build.sh'));

        // Make scripts executable
        chmod(base_path('build.sh'), 0755);
        chmod(base_path('docker-build.sh'), 0755);

        $this->line('  ✓ Created build scripts');
        $this->newLine();
    }

    /**
     * Generate Tauri configuration.
     *
     *
     * @throws TauriPhpException
     */
    protected function generateTauriConfig(): void
    {
        $this->line('⚙️  Generating Tauri configuration...');

        $envManager = new EnvTauriManager;
        $configGenerator = new TauriConfigGenerator($envManager);

        $config = $configGenerator->generate();
        $configGenerator->save($config);

        $this->line('  ✓ Created tauri.conf.json');
        $this->newLine();
    }

    /**
     * Check if Tauri is already initialized.
     */
    protected function isTauriInitialized(): bool
    {
        return file_exists(base_path('src-tauri/Cargo.toml'));
    }

    /**
     * Generate application identifier.
     */
    protected function generateIdentifier(string $appName): string
    {
        $sanitized = strtolower(str_replace(' ', '', $appName));

        return "com.example.{$sanitized}";
    }

    /**
     * Display next steps.
     */
    protected function displayNextSteps(): void
    {
        $this->info('Next Steps:');
        $this->line('  1. Review and customize .env.tauri configuration');
        $this->line('  2. Start development server: php artisan tauri:dev');
        $this->line('  3. Build for production: php artisan tauri:build');
        $this->newLine();
        $this->line('For more information, visit: https://github.com/mucan54/tauri-php');
    }
}
